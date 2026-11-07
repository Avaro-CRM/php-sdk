<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\UnitPrice;

/**
 * The price of a night in a season, or outside every season when season
 * is null. guestsIncluded guests are in the price, each further one pays
 * extraGuestPrice per night.
 */
final class Rate
{
    public function __construct(
        public readonly ?Season $season,
        public readonly UnitPrice $pricePerNight,
        public readonly ?int $guestsIncluded,
        public readonly ?UnitPrice $extraGuestPrice,
        public readonly int $minNights,
    ) {}

    public static function fromData(Data $data): self
    {
        $season = $data->optData('season');
        $extra = $data->optData('extraGuestPrice');

        return new self(
            null === $season ? null : Season::fromData($season),
            UnitPrice::fromData($data->data('pricePerNight')),
            $data->optInt('guestsIncluded'),
            null === $extra ? null : UnitPrice::fromData($extra),
            $data->int('minNights'),
        );
    }
}
