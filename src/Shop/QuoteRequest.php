<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

/**
 * The cart to calculate.
 */
final class QuoteRequest
{
    /**
     * @param list<Line> $lines
     */
    public function __construct(
        public readonly array $lines,
        public readonly ?string $deliveryAddressId = null,
        public readonly ?string $shippingMethod = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'lines' => array_map(static fn(Line $line): array => $line->toArray(), $this->lines),
            'deliveryAddressId' => $this->deliveryAddressId,
            'shippingMethod' => $this->shippingMethod,
        ];
    }
}
