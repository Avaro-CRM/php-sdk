<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;

/**
 * Who is booked for one event, by time of booking.
 */
final class EventAttendees
{
    /**
     * @param list<BookedAttendee> $attendees
     */
    public function __construct(
        public readonly EventRef $event,
        public readonly array $attendees,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(EventRef::fromData($data->data('event')), array_map(BookedAttendee::fromData(...), $data->list('attendees')));
    }
}
