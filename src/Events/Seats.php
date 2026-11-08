<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;

/**
 * Seats of an event: max and free are null without a limit.
 */
final class Seats
{
    public function __construct(
        public readonly ?int $max,
        public readonly int $booked,
        public readonly ?int $free,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->optInt('max'), $data->int('booked'), $data->optInt('free'));
    }

    /**
     * Whether this many attendees still find a seat.
     */
    public function fit(int $attendees): bool
    {
        return null === $this->free || $attendees <= $this->free;
    }
}
