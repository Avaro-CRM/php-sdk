<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use DateTimeImmutable;

/**
 * The days of a unit from from to to.
 */
final class Calendar
{
    /**
     * @param list<CalendarDay> $days
     */
    public function __construct(
        public readonly Ref $unit,
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
        public readonly array $days,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(Ref::fromData($data->data('unit')), $data->dateTime('from'), $data->dateTime('to'), array_map(CalendarDay::fromData(...), $data->list('days')));
    }

    public function day(string $date): ?CalendarDay
    {
        foreach ($this->days as $day) {
            if ($day->date->format('Y-m-d') === $date) {
                return $day;
            }
        }

        return null;
    }
}
