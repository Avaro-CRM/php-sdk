<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\UnitPrice;

/**
 * The unit price from a quantity on.
 */
final class PriceTier
{
    public function __construct(
        public readonly int|float $minQuantity,
        public readonly UnitPrice $unit,
    ) {}
}
