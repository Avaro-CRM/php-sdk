<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;

/**
 * The settings of the shop channel.
 */
final class Channel
{
    /**
     * @param list<PaymentMethod> $paymentMethods
     * @param list<LegalText>     $legal
     */
    public function __construct(
        public readonly string $name,
        public readonly string $currency,
        public readonly PriceDisplay $priceDisplay,
        public readonly bool $catalogPublic,
        public readonly array $paymentMethods,
        public readonly ?int $minimumOrderNet,
        public readonly array $legal,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            $data->string('name'),
            $data->string('currency'),
            PriceDisplay::tryFrom($data->string('priceDisplay')) ?? PriceDisplay::Net,
            $data->bool('catalogPublic'),
            array_map(PaymentMethod::fromData(...), $data->list('paymentMethods')),
            $data->optData('minimumOrder')?->int('net'),
            array_map(LegalText::fromData(...), $data->list('legal')),
        );
    }
}
