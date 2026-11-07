<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Http\Resource;

/**
 * Calculates a cart for the signed-in customer, writing nothing.
 */
final class Quotes extends Resource
{
    public function create(QuoteRequest $request): Quote
    {
        return Quote::fromData($this->transport->send('POST', '/shop/quotes', json: $request->toArray(), customerToken: $this->customer())->data());
    }
}
