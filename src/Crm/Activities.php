<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Crm;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Model\Page;
use Avaro\Sdk\Todos\AddressReference;
use Avaro\Sdk\Todos\ExternalReference;
use Generator;

/**
 * CRM activities of addresses: calls, notes, visits, emails and meetings.
 * The user needs the right to read the address in Avaro.
 */
final class Activities extends Resource
{
    public const NOTE = 'Note';
    public const PHONE_CALL = 'PhoneCall';
    /** A visit. */
    public const MEETING = 'Meeting';
    public const EMAIL = 'Email';
    /** An imported meeting; only read. */
    public const CALENDAR_ENTRY = 'CalendarEntry';

    /**
     * Newest first. The text of each entry is cut after 2,000 characters;
     * get() has the whole text.
     *
     * @param string|ExternalReference|AddressReference $address Avaro ID or external reference of the address
     * @param list<string>                               $types   e.g. [Activities::NOTE, Activities::PHONE_CALL]; every type when empty
     *
     * @return Page<Activity>
     *
     * @throws \Avaro\Sdk\Exception\Forbidden when the user may not read the address
     */
    public function list(string|ExternalReference|AddressReference $address, array $types = [], ?int $limit = null, ?string $cursor = null): Page
    {
        $data = $this->transport->send('GET', '/crm/activities', [
            'address' => \is_string($address) ? $address : null,
            'addressReference' => $address instanceof ExternalReference ? $address->toString() : ($address instanceof AddressReference ? $address->reference()->toString() : null),
            'addressCustomerNumber' => $address instanceof AddressReference ? $address->customerNumber : null,
            'type' => [] === $types ? null : implode(',', $types),
            'limit' => $limit,
            'cursor' => $cursor,
        ])->data();

        return new Page(array_map(static fn($item): Activity => Activity::fromData($item), $data->list('data')), $data->optString('next'));
    }

    /**
     * Every activity of the address, page by page.
     *
     * @param list<string> $types
     *
     * @return Generator<int, Activity>
     */
    public function each(string|ExternalReference|AddressReference $address, array $types = [], int $limit = 100): Generator
    {
        $cursor = null;

        do {
            $page = $this->list($address, $types, $limit, $cursor);

            foreach ($page->items as $activity) {
                yield $activity;
            }

            $cursor = $page->next;
        } while (null !== $cursor);
    }

    /**
     * @throws \Avaro\Sdk\Exception\NotFound also when the user may read none of its addresses
     */
    public function get(string $id): Activity
    {
        return Activity::fromData($this->transport->send('GET', '/crm/activities/' . self::segment($id))->data());
    }

    /**
     * Records an activity in the name of the user, or returns the one the
     * client recorded with the same sourceKey (created false).
     *
     * @throws \Avaro\Sdk\Exception\ValidationFailed
     * @throws \Avaro\Sdk\Exception\Forbidden when the user may not read the address
     */
    public function create(NewActivity $activity): CreatedActivity
    {
        $result = $this->transport->send('POST', '/crm/activities', json: $activity->toArray());

        return new CreatedActivity(Activity::fromData($result->data()), 201 === $result->status);
    }
}
