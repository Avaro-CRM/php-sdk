<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;

final class Availability
{
    public function __construct(
        public readonly AvailabilityStatus $status,
        public readonly bool $orderable,
        public readonly ?string $deliveryTime,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(AvailabilityStatus::tryFrom($data->string('status')) ?? AvailabilityStatus::Unknown, $data->bool('orderable'), $data->optString('deliveryTime'));
    }
}
