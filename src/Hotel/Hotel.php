<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Http\Resource;

/**
 * The module hotel: the channel of a website with holiday apartments or
 * rooms, its units with prices and calendar, quotes and booking requests.
 * No call reads bookings or guests.
 */
final class Hotel extends Resource
{
    public function channel(): Channel
    {
        return Channel::fromData($this->transport->send('GET', '/hotel/channel')->data());
    }

    public function units(): Units
    {
        return new Units($this->transport);
    }

    /**
     * The units free for the stay that can be requested, each with its quote.
     *
     * @return list<Quote>
     */
    public function availability(Stay $stay): array
    {
        return array_map(Quote::fromData(...), $this->transport->send('GET', '/hotel/availability', $stay->query())->data()->list('data'));
    }

    public function quotes(): Quotes
    {
        return new Quotes($this->transport);
    }

    public function requests(): Requests
    {
        return new Requests($this->transport);
    }
}
