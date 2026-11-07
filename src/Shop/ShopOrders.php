<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Orders\OrderSummary;
use InvalidArgumentException;

/**
 * Places orders for the signed-in customer.
 */
final class ShopOrders extends Resource
{
    /**
     * The same key with the same body returns the order stored before.
     *
     * @param string $idempotencyKey printable ASCII, 8 to 128 characters, one per attempt
     *
     * @throws \Avaro\Sdk\Exception\TotalChanged        with the new quote
     * @throws \Avaro\Sdk\Exception\QuoteInvalid        with the quote and its problems
     * @throws \Avaro\Sdk\Exception\IdempotencyConflict
     * @throws \Avaro\Sdk\Exception\ValidationFailed    e.g. ConsentMissing, NotAllowed
     */
    public function place(OrderRequest $order, string $idempotencyKey): PlacedOrder
    {
        if (1 !== preg_match('/^[\x21-\x7E]{8,128}$/', $idempotencyKey)) {
            throw new InvalidArgumentException('An idempotency key is printable ASCII with 8 to 128 characters.');
        }

        $result = $this->transport->send('POST', '/shop/orders', json: $order->toArray(), headers: ['Idempotency-Key' => $idempotencyKey], customerToken: $this->customer());

        return new PlacedOrder(OrderSummary::fromData($result->data()), 200 === $result->status);
    }
}
