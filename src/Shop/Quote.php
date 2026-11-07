<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\Totals;

/**
 * A calculated cart; valid when no line has a problem and the minimum
 * order is reached.
 */
final class Quote
{
    /**
     * @param list<QuoteLine>      $lines
     * @param list<ShippingMethod> $shippingMethods
     * @param list<PaymentMethod>  $paymentMethods
     * @param list<TaxLine>        $taxes
     */
    public function __construct(
        public readonly string $currency,
        public readonly array $lines,
        public readonly Totals $goods,
        public readonly array $shippingMethods,
        public readonly ?string $shippingMethod,
        public readonly Totals $shipping,
        public readonly array $paymentMethods,
        public readonly array $taxes,
        public readonly Totals $total,
        public readonly MinimumOrder $minimumOrder,
        public readonly bool $valid,
    ) {}

    public static function fromData(Data $data): self
    {
        $minimum = $data->data('minimumOrder');

        return new self(
            $data->string('currency'),
            array_map(QuoteLine::fromData(...), $data->list('lines')),
            Totals::fromData($data->data('goods')),
            array_map(ShippingMethod::fromData(...), $data->list('shippingMethods')),
            $data->optString('shippingMethod'),
            Totals::fromData($data->data('shipping')),
            array_map(PaymentMethod::fromData(...), $data->list('paymentMethods')),
            array_map(static fn(Data $tax): TaxLine => new TaxLine($tax->string('rate'), $tax->int('net'), $tax->int('tax')), $data->list('taxes')),
            Totals::fromData($data->data('total')),
            new MinimumOrder($minimum->int('net'), $minimum->bool('reached'), $minimum->int('missing')),
            $data->bool('valid'),
        );
    }
}
