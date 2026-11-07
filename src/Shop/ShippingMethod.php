<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\Totals;

/**
 * A way of shipping with its costs for this quote.
 */
final class ShippingMethod
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly Totals $costs,
        public readonly ?int $freeFrom,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('key'), $data->string('title'), Totals::fromData($data->data('costs')), $data->optInt('freeFrom'));
    }
}
