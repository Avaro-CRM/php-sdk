<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Orders;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Model\Page;

/**
 * Orders of the signed-in customer, newest first.
 */
final class Orders extends Resource
{
    /**
     * @return Page<OrderSummary>
     */
    public function list(?int $limit = null, ?string $cursor = null): Page
    {
        $data = $this->transport->send('GET', '/orders/orders', ['limit' => $limit, 'cursor' => $cursor], customerToken: $this->customer())->data();

        return new Page(array_map(OrderSummary::fromData(...), $data->list('data')), $data->optString('next'));
    }

    /**
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function get(string $id): Order
    {
        return Order::fromData($this->transport->send('GET', '/orders/orders/' . self::segment($id), customerToken: $this->customer())->data());
    }
}
