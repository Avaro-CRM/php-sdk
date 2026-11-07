<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\UnitPrice;

/**
 * What a pet costs per night, with a note such as "on request".
 */
final class PetPrice
{
    public function __construct(
        public readonly UnitPrice $pricePerNight,
        public readonly ?string $note,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(UnitPrice::fromData($data->data('pricePerNight')), $data->optString('note'));
    }
}
