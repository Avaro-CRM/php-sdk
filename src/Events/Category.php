<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\UnitPrice;

/**
 * A course: price per attendee and attendee limits Avaro suggests for its
 * events, each null when none is set. An event may differ.
 */
final class Category
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $subtitle,
        public readonly ?string $parent,
        public readonly string $currency,
        public readonly ?UnitPrice $price,
        public readonly ?int $attendantsMin,
        public readonly ?int $attendantsMax,
    ) {}

    public static function fromData(Data $data): self
    {
        $price = $data->optData('price');

        return new self(
            $data->string('id'),
            $data->string('title'),
            $data->optString('subtitle'),
            $data->optString('parent'),
            $data->string('currency'),
            null === $price ? null : UnitPrice::fromData($price),
            $data->optInt('attendantsMin'),
            $data->optInt('attendantsMax'),
        );
    }
}
