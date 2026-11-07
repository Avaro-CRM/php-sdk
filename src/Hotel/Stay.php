<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * A stay from arrival to departure (the day of leaving); unit is null to
 * ask every unit for availability.
 */
final class Stay
{
    public readonly DateTimeImmutable $arrival;

    public readonly DateTimeImmutable $departure;

    public function __construct(
        DateTimeInterface $arrival,
        DateTimeInterface $departure,
        public readonly Guests $guests,
        public readonly ?string $unit = null,
    ) {
        $this->arrival = DateTimeImmutable::createFromInterface($arrival)->setTime(0, 0);
        $this->departure = DateTimeImmutable::createFromInterface($departure)->setTime(0, 0);

        if ($this->departure <= $this->arrival) {
            throw new InvalidArgumentException('The departure is after the arrival.');
        }
    }

    public function nights(): int
    {
        return (int) $this->arrival->diff($this->departure)->days;
    }

    /**
     * @return array{unit: ?string, arrival: string, departure: string, guests: array{adults: int, children: int, infants: int, pets: int}}
     */
    public function toArray(): array
    {
        return ['unit' => $this->unit, 'arrival' => $this->arrival->format('Y-m-d'), 'departure' => $this->departure->format('Y-m-d'), 'guests' => $this->guests->toArray()];
    }

    /**
     * @return array<string, string|int>
     */
    public function query(): array
    {
        return ['arrival' => $this->arrival->format('Y-m-d'), 'departure' => $this->departure->format('Y-m-d'), ...$this->guests->toArray()];
    }
}
