<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

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
