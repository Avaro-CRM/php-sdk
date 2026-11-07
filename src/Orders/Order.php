<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Orders;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\PostalAddress;
use Avaro\Sdk\Model\Totals;

/**
 * One order with its lines and addresses.
 */
final class Order
{
    /**
     * @param list<OrderLine> $lines
     * @param list<string>    $invoiceIds
     */
    public function __construct(
        public readonly OrderSummary $summary,
        public readonly array $lines,
        public readonly Totals $goods,
        public readonly Totals $shipping,
        public readonly ?string $shippingMethod,
        public readonly ?string $paymentMethod,
        public readonly ?string $comment,
        public readonly PostalAddress $billingAddress,
        public readonly PostalAddress $deliveryAddress,
        public readonly array $invoiceIds,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            OrderSummary::fromData($data),
            array_map(OrderLine::fromData(...), $data->list('lines')),
            Totals::fromData($data->data('goods')),
            Totals::fromData($data->data('shipping')),
            $data->optString('shippingMethod'),
            $data->optString('paymentMethod'),
            $data->optString('comment'),
            PostalAddress::fromData($data->data('billingAddress')),
            PostalAddress::fromData($data->data('deliveryAddress')),
            $data->strings('invoiceIds'),
        );
    }
}
