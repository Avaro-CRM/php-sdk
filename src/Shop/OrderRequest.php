<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

/**
 * An order; expectedGross is the gross total the customer saw, in cents.
 */
final class OrderRequest
{
    /**
     * @param list<Line>    $lines
     * @param list<Consent> $consents
     */
    public function __construct(
        public readonly array $lines,
        public readonly string $shippingMethod,
        public readonly string $paymentMethod,
        public readonly array $consents,
        public readonly int $expectedGross,
        public readonly ?string $deliveryAddressId = null,
        public readonly ?string $reference = null,
        public readonly ?string $comment = null,
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
            'paymentMethod' => $this->paymentMethod,
            'reference' => $this->reference,
            'comment' => $this->comment,
            'consents' => array_map(static fn(Consent $consent): array => ['key' => $consent->key, 'version' => $consent->version], $this->consents),
            'expectedTotal' => ['gross' => $this->expectedGross],
        ];
    }
}
