<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;
use DateTimeImmutable;

/**
 * Someone booked for an event: names and when, nothing else.
 */
final class BookedAttendee
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?DateTimeImmutable $bookedAt = null,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('firstName'), $data->string('lastName'), $data->optDateTime('bookedAt'));
    }
}
