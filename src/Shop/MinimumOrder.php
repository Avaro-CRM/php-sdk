<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

/**
 * The minimum net goods value; 0 without one.
 */
final class MinimumOrder
{
    public function __construct(
        public readonly int $net,
        public readonly bool $reached,
        public readonly int $missing,
    ) {}
}
