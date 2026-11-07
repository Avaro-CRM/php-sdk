<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

final class TaxLine
{
    public function __construct(
        public readonly string $rate,
        public readonly int $net,
        public readonly int $tax,
    ) {}
}
