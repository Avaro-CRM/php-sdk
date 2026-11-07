<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

/**
 * A product and its quantity in pieces for a quote or an order.
 */
final class Line
{
    public function __construct(
        public readonly string $productId,
        public readonly int|float $quantity,
    ) {}

    /**
     * @return array{productId: string, quantity: int|float}
     */
    public function toArray(): array
    {
        return ['productId' => $this->productId, 'quantity' => $this->quantity];
    }
}
