<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Http\Resource;
use InvalidArgumentException;

/**
 * The module events: the channel of a website with courses, its
 * categories, upcoming events with free seats and bookings. Only
 * attendees() reads names of attendees, with its own scope.
 */
final class Events extends Resource
{
    /** Avaro answers for at most this many events per call. */
    public const ATTENDEES_PER_CALL = 50;

    public function channel(): Channel
    {
        return Channel::fromData($this->transport->send('GET', '/events/channel')->data());
    }

    public function categories(): Categories
    {
        return new Categories($this->transport);
    }

    public function events(): EventList
    {
        return new EventList($this->transport);
    }

    public function bookings(): Bookings
    {
        return new Bookings($this->transport);
    }

    /**
     * First and last name and time of booking of who is booked for these
     * events, one entry per event the channel shows, in the given order.
     * Needs the scope events.attendees:read; meant for pages behind a
     * password. More than 50 events take several calls.
     *
     * @param list<string> $events IDs from events()
     *
     * @return list<EventAttendees>
     */
    public function attendees(array $events): array
    {
        $ids = array_values(array_unique(array_map('trim', $events)));

        if ([] === $ids || \in_array('', $ids, true)) {
            throw new InvalidArgumentException('Pass the IDs of one or more events.');
        }

        $result = [];

        foreach (array_chunk($ids, self::ATTENDEES_PER_CALL) as $chunk) {
            $data = $this->transport->send('GET', '/events/attendees', ['events' => implode(',', $chunk)])->data();
            $result = [...$result, ...array_map(EventAttendees::fromData(...), $data->list('data'))];
        }

        return $result;
    }
}
