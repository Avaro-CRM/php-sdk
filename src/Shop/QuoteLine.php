<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\Totals;
use Avaro\Sdk\Model\UnitPrice;

/**
 * A line of a quote; problems such as BelowMinQuantity or NoPrice.
 */
final class QuoteLine
{
    /**
     * @param list<string> $problems
     */
    public function __construct(
        public readonly string $productId,
        public readonly ?string $sku,
        public readonly ?string $title,
        public readonly int|float $quantity,
        public readonly ?UnitPrice $unit,
        public readonly ?Totals $total,
        public readonly array $problems,
    ) {}

    public static function fromData(Data $data): self
    {
        $unit = $data->optData('unit');
        $total = $data->optData('total');

        return new self(
            $data->string('productId'),
            $data->optString('sku'),
            $data->optString('title'),
            $data->number('quantity'),
            null === $unit ? null : UnitPrice::fromData($unit),
            null === $total ? null : Totals::fromData($total),
            $data->strings('problems'),
        );
    }
}
