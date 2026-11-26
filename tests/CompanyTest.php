<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Company\AdditionalField;
use Avaro\Sdk\Company\AddressContact;
use Avaro\Sdk\Company\AddressContactField;
use Avaro\Sdk\Company\AddressChanges;
use Avaro\Sdk\Company\Address;
use Avaro\Sdk\Company\AddressData;
use Avaro\Sdk\Company\User;
use Avaro\Sdk\Company\UserProfile;
use Avaro\Sdk\Exception\Conflict;
use Avaro\Sdk\Exception\Forbidden;
use Avaro\Sdk\Exception\UserLinkRejected;
use Avaro\Sdk\Exception\UserLinkRequested;
use Avaro\Sdk\Tests\Support\Fixtures;

final class CompanyTest extends TestCase
{
    public function testProvisionCreatesOnceAndGetReads(): void
    {
        $this->http
            ->answer(201, Fixtures::load('company-user'))
            ->answer(200, Fixtures::load('company-user'))
            ->answer(200, Fixtures::load('company-user'));
        $users = $this->avaro()->company()->users();
        $profile = new UserProfile('Mara.Stein@example.test', 'Mara', 'Stein');

        $created = $users->provision('ExamplePlatform', '01HXA7Q2Z6V7C1M4K9P0R3S5T8', $profile);
        self::assertSame(['PUT', 'https://app.avaro.test/rest/v3/company/users/by-external-id/ExamplePlatform/01HXA7Q2Z6V7C1M4K9P0R3S5T8'], [$this->http->last()->getMethod(), (string) $this->http->last()->getUri()]);
        self::assertSame(['email' => 'Mara.Stein@example.test', 'firstName' => 'Mara', 'lastName' => 'Stein'], $this->http->lastJson());
        self::assertTrue($created->created);
        self::assertTrue($created->isActive());
        self::assertSame(User::PROVISIONED, $created->origin);
        self::assertSame('01HXA7Q2Z6V7C1M4K9P0R3S5T8', $created->externalIds[0]->externalId);

        self::assertFalse($users->provision('ExamplePlatform', '01HXA7Q2Z6V7C1M4K9P0R3S5T8', $profile)->created);
        self::assertSame('mara.stein@example.test', $users->get('ExamplePlatform', '01HXA7Q2Z6V7C1M4K9P0R3S5T8')->email);
    }

    public function testATakenEmailAndAForeignSystemAreRefused(): void
    {
        $this->http
            ->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/user-email-taken', 'title' => 'Another Avaro user has this email.', 'status' => 409])
            ->answer(403, ['type' => 'https://app.avaro.test/rest/v3/problems/foreign-system', 'title' => 'Not allowed.', 'status' => 403]);
        $users = $this->avaro()->company()->users();

        try {
            $users->provision('ExamplePlatform', 'u-1', new UserProfile('taken@example.test', '', 'Muster'));
            self::fail('No exception.');
        } catch (Conflict $error) {
            self::assertSame('user-email-taken', $error->slug());
        }

        $this->expectException(Forbidden::class);
        $users->provision('OtherSystem', 'u-1', new UserProfile('a@example.test', '', 'Muster'));
    }

    public function testAnEmailOfAnEmployeeAsksTheTenantToLink(): void
    {
        $this->http
            ->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/user-link-requested', 'title' => 'An Avaro user has this email; linking needs the approval of the tenant.', 'status' => 409])
            ->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/user-link-rejected', 'title' => 'The tenant rejected linking this ID to the Avaro user with this email.', 'status' => 409]);
        $users = $this->avaro()->company()->users();

        try {
            $users->provision('ExamplePlatform', 'u-1', new UserProfile('anna@example.test', 'Anna', 'Weber'));
            self::fail('No exception.');
        } catch (UserLinkRequested $error) {
            self::assertSame([409, 'user-link-requested'], [$error->status(), $error->slug()]);
        }

        $this->expectException(UserLinkRejected::class);
        $users->provision('ExamplePlatform', 'u-1', new UserProfile('anna@example.test', 'Anna', 'Weber'));
    }

    public function testAddressProvisionSendsOnlyTheFieldsSetAndCreatesOnce(): void
    {
        $this->http
            ->answer(201, Fixtures::load('company-address'))
            ->answer(200, Fixtures::load('company-address'))
            ->answer(200, Fixtures::load('company-address'));
        $addresses = $this->avaro()->company()->addresses();
        $data = AddressData::named('Muster Bau GmbH')->customerNumber('10042')->location('Hauptstraße', '1', '26122', 'Oldenburg', 'DE')->phone(null);

        $created = $addresses->provision('ExamplePlatform', '01HXB0COMPANY0000000000000', $data);
        self::assertSame(['PUT', 'https://app.avaro.test/rest/v3/company/addresses/by-external-id/ExamplePlatform/01HXB0COMPANY0000000000000'], [$this->http->last()->getMethod(), (string) $this->http->last()->getUri()]);
        self::assertSame(['name' => 'Muster Bau GmbH', 'phone' => null, 'country' => 'DE', 'city' => 'Oldenburg', 'zipcode' => '26122', 'streetNumber' => '1', 'street' => 'Hauptstraße', 'customerNumber' => '10042'], $this->http->lastJson());
        self::assertTrue($created->created);
        self::assertSame([Address::PROVISIONED, Address::ACTIVE, '4711', '10042', 'DE'], [$created->origin, $created->status, $created->id, $created->customerNumber, $created->country]);
        self::assertNull($created->phone);
        self::assertSame('01HXB0COMPANY0000000000000', $created->externalIds[0]->externalId);

        self::assertFalse($addresses->provision('ExamplePlatform', '01HXB0COMPANY0000000000000', AddressData::named('Muster Bau GmbH'))->created);
        self::assertSame(['name' => 'Muster Bau GmbH'], $this->http->lastJson());
        self::assertSame('Oldenburg', $addresses->get('ExamplePlatform', '01HXB0COMPANY0000000000000')->city);
    }

    public function testTheTargetAccountAndAdditionalFieldsAreReadAndTheTargetAccountIsSet(): void
    {
        $this->http
            ->answer(200, Fixtures::load('company-address'))
            ->answer(200, Fixtures::load('company-address-expanded'))
            ->answer(200, Fixtures::load('company-address-expanded'))
            ->answer(200, Fixtures::load('company-address'));
        $addresses = $this->avaro()->company()->addresses();

        $plain = $addresses->get('ExamplePlatform', 'c-1');
        self::assertSame('', $this->http->last()->getUri()->getQuery());
        self::assertNull($plain->targetAccount);
        self::assertNull($plain->additionalFields, 'Not requested.');

        $expanded = $addresses->get('ExamplePlatform', 'c-1', additionalFields: true);
        self::assertSame('expand=additionalFields', $this->http->last()->getUri()->getQuery());
        self::assertTrue($expanded->targetAccount);
        $kam = $expanded->additionalField('KAM-Fit');
        self::assertSame(['4', AdditionalField::TAGS, true, '2528', 'Obi'], [$kam?->id, $kam?->type, $kam?->stored, $kam?->tags()[0]->id, $kam?->tags()[0]->title]);
        self::assertSame([null, true, []], [$expanded->additionalField('Mitarbeiterzahl')?->value, $expanded->additionalField('Rahmenvertrag')?->value, $expanded->additionalField('Rahmenvertrag')?->tags()]);
        self::assertNull($expanded->additionalField('Fehlt'));

        $set = $addresses->setTargetAccount('ExamplePlatform', 'c-1', true, additionalFields: true);
        self::assertSame(['PATCH', '/rest/v3/company/addresses/by-external-id/ExamplePlatform/c-1', 'expand=additionalFields'], [$this->http->last()->getMethod(), $this->http->last()->getUri()->getPath(), $this->http->last()->getUri()->getQuery()]);
        self::assertSame(['targetAccount' => true], $this->http->lastJson());
        self::assertTrue($set->targetAccount);

        $addresses->setTargetAccount('ExamplePlatform', 'c-1', null);
        self::assertSame(['targetAccount' => null], $this->http->lastJson(), 'null clears the target account.');
    }

    public function testContactsAreReadOnlyOnRequestAndCombineWithAdditionalFields(): void
    {
        $this->http
            ->answer(200, Fixtures::load('company-address'))
            ->answer(200, Fixtures::load('company-address-expanded'))
            ->answer(200, Fixtures::load('company-address-expanded'));
        $addresses = $this->avaro()->company()->addresses();

        self::assertNull($addresses->get('ExamplePlatform', 'c-1')->contacts, 'Not requested.');

        $address = $addresses->get('ExamplePlatform', 'c-1', contacts: true);
        self::assertSame('expand=contacts', $this->http->last()->getUri()->getQuery());
        self::assertCount(2, $address->contacts ?? []);
        [$erika, $max] = $address->contacts ?? [];
        self::assertInstanceOf(AddressContact::class, $erika);
        self::assertSame(['contact-public-7', 'Dr.', 'Erika', 'Muster', 'Geschäftsführung', 'Erika Muster'], [$erika->id, $erika->title, $erika->firstName, $erika->lastName, $erika->position, $erika->name()]);
        self::assertSame('e.muster@muster-bau.example', $erika->email(), 'The primary email wins.');
        self::assertSame('+49 170 1234567', $erika->phone(), 'Falls back to the mobile number.');
        self::assertSame(['11', '12'], array_map(fn(AddressContactField $field) => $field->id, $erika->fieldsOfType(AddressContactField::EMAIL)));
        self::assertSame(['business', false], [$erika->fields[0]->scope, $erika->fields[0]->primary]);
        self::assertSame([null, 'Max', null, null, null, []], [$max->title, $max->name(), $max->email(), $max->phone(), $max->position, $max->fields]);

        $addresses->get('ExamplePlatform', 'c-1', additionalFields: true, contacts: true);
        self::assertSame('expand=additionalFields%2Ccontacts', $this->http->last()->getUri()->getQuery());
    }

    public function testAContactWithoutPrimaryFieldsUsesTheFirstFilledOne(): void
    {
        $contact = new AddressContact('c', null, null, 'Muster', null, [
            new AddressContactField('1', AddressContactField::EMAIL, null, ''),
            new AddressContactField('2', AddressContactField::EMAIL, null, 'a@example.test'),
            new AddressContactField('3', AddressContactField::PHONE, null, '0441 1'),
            new AddressContactField('4', AddressContactField::MOBILE, null, '0170 1', true),
        ]);

        self::assertSame(['Muster', 'a@example.test', '0441 1'], [$contact->name(), $contact->email(), $contact->phone()]);
    }

    public function testAdditionalFieldsAreDefinedSuggestedAndChanged(): void
    {
        $this->http
            ->answer(200, ['data' => [Fixtures::load('company-address-field'), ['id' => '4', 'title' => 'KAM-Fit', 'type' => 'Tags', 'mandatory' => false, 'onlyCompanies' => true, 'options' => null]]])
            ->answer(200, ['data' => [['id' => '2528', 'title' => 'Obi']]])
            ->answer(200, Fixtures::load('company-address-expanded'))
            ->answer(200, Fixtures::load('company-address'));
        $addresses = $this->avaro()->company()->addresses();

        $fields = $addresses->fields();
        self::assertSame(['5', 'Select', ['Bau', 'Elektro'], true], [$fields[0]->id, $fields[0]->type, $fields[0]->options, $fields[0]->onlyCompanies]);
        self::assertNull($fields[1]->options);

        $tags = $addresses->fieldTags('4', 'Ob', 10);
        self::assertSame('/rest/v3/company/address-fields/4/tags', $this->http->last()->getUri()->getPath());
        parse_str($this->http->last()->getUri()->getQuery(), $query);
        self::assertSame(['limit' => '10', 'search' => 'Ob'], $query);
        self::assertSame(['2528', 'Obi'], [$tags[0]->id, $tags[0]->title]);

        $changes = AddressChanges::new()->targetAccount(false)->tags('4', existing: ['2528'], new: ['Neu'])->value('5', 'Bau')->value('6', 42)->clear('7');
        $address = $addresses->update('ExamplePlatform', 'c-1', $changes, additionalFields: true);
        self::assertSame(['PATCH', 'expand=additionalFields'], [$this->http->last()->getMethod(), $this->http->last()->getUri()->getQuery()]);
        self::assertSame('{"additionalFields":{"4":[{"id":"2528"},{"title":"Neu"}],"5":"Bau","6":42,"7":null},"targetAccount":false}', (string) $this->http->last()->getBody());
        self::assertSame('KAM-Fit', $address->additionalFields[0]->title ?? null);

        $addresses->update('ExamplePlatform', 'c-1', AddressChanges::new());
        self::assertSame('[]', (string) $this->http->last()->getBody(), 'No changes: the address as it is.');
    }

    public function testAnAmbiguousCustomerNumberIsRefused(): void
    {
        $this->http->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/customer-number-ambiguous', 'title' => 'Several addresses have this customer number.', 'status' => 409]);

        try {
            $this->avaro()->company()->addresses()->provision('ExamplePlatform', 'c-1', AddressData::named('Dup')->customerNumber('7'));
            self::fail('No exception.');
        } catch (Conflict $error) {
            self::assertSame('customer-number-ambiguous', $error->slug());
        }
    }
}
