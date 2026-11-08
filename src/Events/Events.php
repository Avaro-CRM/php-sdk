<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Http\Resource;

/**
 * The module events: the channel of a website with courses, its
 * categories, upcoming events with free seats and bookings. No call
 * reads bookings or attendees.
 */
final class Events extends Resource
{
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
}
