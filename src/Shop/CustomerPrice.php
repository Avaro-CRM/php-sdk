<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\UnitPrice;

/**
 * The price for the signed-in customer, from Avaro's price service.
 */
final class CustomerPrice
{
    /**
     * @param list<PriceTier> $tiers
     */
    public function __construct(
        public readonly string $currency,
        public readonly UnitPrice $unit,
        public readonly ?UnitPrice $regularUnit,
        public readonly ?string $discountPercentage,
        public readonly ?BasePrice $basePrice,
        public readonly array $tiers,
        public readonly ?RetailLabel $retailLabel,
    ) {}

    public static function fromData(Data $data): self
    {
        $regular = $data->optData('regularUnit');
        $base = $data->optData('basePrice');
        $label = $data->optData('retailLabel');

        return new self(
            $data->string('currency'),
            UnitPrice::fromData($data->data('unit')),
            null === $regular ? null : UnitPrice::fromData($regular),
            $data->optString('discountPercentage'),
            null === $base ? null : new BasePrice($base->string('gross'), $base->number('quantity'), $base->string('unit')),
            array_map(static fn(Data $tier): PriceTier => new PriceTier($tier->number('minQuantity'), UnitPrice::fromData($tier->data('unit'))), $data->optList('tiers') ?? []),
            null === $label ? null : new RetailLabel($label->optString('gtin'), $label->optString('retailPrice')),
        );
    }
}
