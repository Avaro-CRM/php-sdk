<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

/**
 * The price per reference quantity, e.g. per kg (PAngV).
 */
final class BasePrice
{
    public function __construct(
        public readonly string $gross,
        public readonly int|float $quantity,
        public readonly string $unit,
    ) {}
}
