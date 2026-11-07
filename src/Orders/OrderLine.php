<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Orders;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\Totals;
use Avaro\Sdk\Model\UnitPrice;

final class OrderLine
{
    public function __construct(
        public readonly ?string $productId,
        public readonly ?string $sku,
        public readonly string $title,
        public readonly int|float $quantity,
        public readonly UnitPrice $unit,
        public readonly Totals $total,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->optString('productId'), $data->optString('sku'), $data->string('title'), $data->number('quantity'), UnitPrice::fromData($data->data('unit')), Totals::fromData($data->data('total')));
    }
}
