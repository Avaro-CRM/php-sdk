<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

/**
 * GTIN and recommended retail price (gross) for labels in the store of
 * the customer.
 */
final class RetailLabel
{
    public function __construct(
        public readonly ?string $gtin,
        public readonly ?string $retailPrice,
    ) {}
}
