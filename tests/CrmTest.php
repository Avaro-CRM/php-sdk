<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Crm\Activities;
use Avaro\Sdk\Crm\Call;
use Avaro\Sdk\Crm\NewActivity;
use Avaro\Sdk\Tests\Support\Fixtures;
use Avaro\Sdk\Todos\AddressReference;
use DateTimeImmutable;

final class CrmTest extends TestCase
{
    public function testListSendsTheAddressAndTypesAndReadsTheActivities(): void
    {
        $this->http->answer(200, ['data' => [Fixtures::load('crm-activity')], 'next' => 'eyJpZCI6MX0']);

        $page = $this->avaro()->crm()->activities()->list(new AddressReference('ExamplePlatform', '01HXB0COMPANY0000000000000', '10042'), [Activities::NOTE, Activities::PHONE_CALL], 20);

        self::assertSame('/rest/v3/crm/activities', $this->http->last()->getUri()->getPath());
        parse_str($this->http->last()->getUri()->getQuery(), $query);
        self::assertSame(['addressReference' => 'ExamplePlatform:01HXB0COMPANY0000000000000', 'addressCustomerNumber' => '10042', 'type' => 'Note,PhoneCall', 'limit' => '20'], $query);
        self::assertSame('eyJpZCI6MX0', $page->next);

        $activity = $page->items[0];
        self::assertSame(['144256', 'PhoneCall', '2026-11-24 08:15', 'Outgoing', 'Api', true], [$activity->id, $activity->type, $activity->occurredAt->format('Y-m-d H:i'), $activity->direction, $activity->origin, $activity->canEdit]);
        self::assertSame(['Anna Weber', '01HXA7Q2Z6V7C1M4K9P0R3S5T8'], [$activity->author?->name, $activity->author?->referenceOf('ExamplePlatform')]);
        self::assertSame(['Florian Berg', 'Attendee'], [$activity->contact?->name, $activity->participants[0]->role]);
        self::assertSame([125, Call::COMPLETED, 'Entscheider erreicht'], [$activity->call?->durationSeconds, $activity->call?->status, $activity->call?->outcome]);
        self::assertSame('10042', $activity->addresses[0]->customerNumber);
    }

    public function testCreateSendsTheCallAndTellsWhetherItWasRecorded(): void
    {
        $this->http->answer(201, Fixtures::load('crm-activity'))->answer(200, Fixtures::load('crm-activity'));
        $call = NewActivity::phoneCall('4711', 'Rückruf am Montag vereinbart.', 'Outgoing', 125, Call::COMPLETED, 'Entscheider erreicht', new DateTimeImmutable('2026-11-24 09:15:00', new \DateTimeZone('Europe/Berlin')), '815', 'Call:1');

        $created = $this->avaro()->crm()->activities()->create($call);
        $again = $this->avaro()->crm()->activities()->create($call);

        self::assertSame('POST', $this->http->last()->getMethod());
        self::assertSame([
            'type' => 'PhoneCall',
            'address' => ['id' => '4711'],
            'contact' => ['id' => '815'],
            'text' => 'Rückruf am Montag vereinbart.',
            'occurredAt' => '2026-11-24T08:15:00Z',
            'direction' => 'Outgoing',
            'call' => ['durationSeconds' => 125, 'status' => 'Completed', 'outcome' => 'Entscheider erreicht'],
            'sourceKey' => 'Call:1',
        ], json_decode((string) $this->http->last()->getBody(), true));
        self::assertSame([true, false], [$created->created, $again->created]);
    }

    public function testANoteNeedsOnlyAddressAndText(): void
    {
        self::assertSame(['address' => ['reference' => ['system' => 'ExamplePlatform', 'externalId' => '01HX'], 'customerNumber' => '10042'], 'text' => 'Notiz'], NewActivity::note(new AddressReference('ExamplePlatform', '01HX', '10042'), 'Notiz')->toArray());
    }

    public function testGetReadsOneActivity(): void
    {
        $this->http->answer(200, Fixtures::load('crm-activity'));

        self::assertSame('144256', $this->avaro()->crm()->activities()->get('144256')->id);
        self::assertSame('/rest/v3/crm/activities/144256', $this->http->last()->getUri()->getPath());
    }
}
