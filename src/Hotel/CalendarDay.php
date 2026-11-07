<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use DateTimeImmutable;

/**
 * One day: whether a stay may begin (arrival) or end (departure) on it,
 * and the minimum nights of a stay beginning on it.
 */
final class CalendarDay
{
    public function __construct(
        public readonly DateTimeImmutable $date,
        public readonly DayStatus $status,
        public readonly bool $arrival,
        public readonly bool $departure,
        public readonly int $minNights,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            $data->dateTime('date'),
            // A status the SDK does not know counts as booked.
            DayStatus::tryFrom($data->string('status')) ?? DayStatus::Booked,
            $data->bool('arrival'),
            $data->bool('departure'),
            $data->int('minNights'),
        );
    }
}
