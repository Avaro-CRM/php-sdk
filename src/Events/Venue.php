<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;

/**
 * Where an event takes place; street with the number.
 */
final class Venue
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $street,
        public readonly ?string $postalCode,
        public readonly ?string $city,
        public readonly ?string $country,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->string('title'), $data->optString('street'), $data->optString('postalCode'), $data->optString('city'), $data->optString('country'));
    }
}
