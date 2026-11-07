<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Orders\OrderSummary;

/**
 * An order placed now, or before with the same key (repeated).
 */
final class PlacedOrder
{
    public function __construct(
        public readonly OrderSummary $order,
        public readonly bool $repeated,
    ) {}
}
