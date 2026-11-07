<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Model\Page;
use Generator;

/**
 * Products of the channel; with a customer token with prices.
 */
final class Products extends Resource
{
    /**
     * @return Page<ProductSummary>
     */
    public function list(?string $category = null, ?string $query = null, ?int $limit = null, ?string $cursor = null): Page
    {
        $data = $this->transport->send('GET', '/shop/products', ['category' => $category, 'query' => $query, 'limit' => $limit, 'cursor' => $cursor], customerToken: $this->customerToken)->data();

        return new Page(array_map(ProductSummary::fromData(...), $data->list('data')), $data->optString('next'));
    }

    /**
     * Every product, page by page.
     *
     * @return Generator<int, ProductSummary>
     */
    public function each(?string $category = null, ?string $query = null, int $limit = 100): Generator
    {
        $cursor = null;

        do {
            $page = $this->list($category, $query, $limit, $cursor);

            foreach ($page->items as $product) {
                yield $product;
            }

            $cursor = $page->next;
        } while (null !== $cursor);
    }

    /**
     * @param string $id the ID or a slug ending in it
     *
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function get(string $id): Product
    {
        return Product::fromData($this->transport->send('GET', '/shop/products/' . self::segment($id), customerToken: $this->customerToken)->data());
    }
}
