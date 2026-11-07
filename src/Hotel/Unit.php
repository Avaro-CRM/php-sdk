<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use DateTimeInterface;

/**
 * A holiday apartment or room type with its limits, rates and fees.
 * Texts and images are the website's own.
 */
final class Unit
{
    /**
     * @param list<Rate> $rates the default rate (season null) and the rates of seasons not over
     * @param list<Fee>  $fees
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly Ref $property,
        public readonly int $maxGuests,
        public readonly int $maxInfants,
        public readonly int $maxPets,
        public readonly int $minNights,
        public readonly string $currency,
        public readonly array $rates,
        public readonly ?PetPrice $pets,
        public readonly array $fees,
    ) {}

    public static function fromData(Data $data): self
    {
        $pets = $data->optData('pets');

        return new self(
            $data->string('id'),
            $data->string('title'),
            Ref::fromData($data->data('property')),
            $data->int('maxGuests'),
            $data->int('maxInfants'),
            $data->int('maxPets'),
            $data->int('minNights'),
            $data->string('currency'),
            array_map(Rate::fromData(...), $data->list('rates')),
            null === $pets ? null : PetPrice::fromData($pets),
            array_map(Fee::fromData(...), $data->list('fees')),
        );
    }

    /**
     * The rate of the night of this date: of its season, else the default.
     */
    public function rateOn(DateTimeInterface $date): ?Rate
    {
        $day = $date->format('Y-m-d');
        $default = null;

        foreach ($this->rates as $rate) {
            if (null === $rate->season) {
                $default ??= $rate;
            } elseif ($rate->season->from->format('Y-m-d') <= $day && $day <= $rate->season->to->format('Y-m-d')) {
                return $rate;
            }
        }

        return $default;
    }
}
