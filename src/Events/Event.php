<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\UnitPrice;
use DateTimeImmutable;

/**
 * An upcoming event of a course with its price per attendee (null when
 * free of charge), seats, booking status and the fields a booking asks
 * for.
 */
final class Event
{
    /**
     * @param list<Field> $fields
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $number,
        public readonly string $title,
        public readonly ?string $subtitle,
        public readonly ?Ref $category,
        public readonly DateTimeImmutable $startsAt,
        public readonly ?DateTimeImmutable $endsAt,
        public readonly ?Venue $venue,
        public readonly bool $online,
        public readonly string $currency,
        public readonly ?UnitPrice $price,
        public readonly ?int $attendantsMin,
        public readonly ?int $attendantsMax,
        public readonly Seats $seats,
        public readonly BookingWindow $booking,
        public readonly array $fields,
    ) {}

    public static function fromData(Data $data): self
    {
        $category = $data->optData('category');
        $venue = $data->optData('venue');
        $price = $data->optData('price');

        return new self(
            $data->string('id'),
            $data->optString('number'),
            $data->string('title'),
            $data->optString('subtitle'),
            null === $category ? null : Ref::fromData($category),
            $data->dateTime('startsAt'),
            $data->optDateTime('endsAt'),
            null === $venue ? null : Venue::fromData($venue),
            $data->bool('online', false),
            $data->string('currency'),
            null === $price ? null : UnitPrice::fromData($price),
            $data->optInt('attendantsMin'),
            $data->optInt('attendantsMax'),
            Seats::fromData($data->data('seats')),
            BookingWindow::fromData($data->data('booking')),
            array_map(Field::fromData(...), $data->optList('fields') ?? []),
        );
    }

    /**
     * Whether this many attendees can book now: open, with a seat each,
     * and no required field of a type the SDK does not know.
     */
    public function bookable(int $attendees = 1): bool
    {
        foreach ($this->fields as $field) {
            if ($field->required && null === $field->type) {
                return false;
            }
        }

        return BookingStatus::Open === $this->booking->status && $attendees >= 1 && $this->seats->fit($attendees);
    }

    /**
     * The gross total in cents for this many attendees, as Avaro checks it:
     * the gross price per attendee in cents times the attendees.
     */
    public function totalGross(int $attendees): int
    {
        return null === $this->price ? 0 : self::cents($this->price->gross) * $attendees;
    }

    public function field(string $key): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->key === $key) {
                return $field;
            }
        }

        return null;
    }

    /** A decimal string such as 40.0000 in cents, rounded half up, without floats. */
    private static function cents(string $amount): int
    {
        if (1 !== preg_match('/^(-?)(\d+)(?:\.(\d+))?$/D', $amount, $parts)) {
            throw new \UnexpectedValueException('Not an amount: ' . $amount);
        }

        $decimals = str_pad($parts[3] ?? '', 3, '0');
        $cents = (int) $parts[2] * 100 + (int) substr($decimals, 0, 2) + ((int) $decimals[2] >= 5 ? 1 : 0);

        return '-' === $parts[1] ? -$cents : $cents;
    }
}
