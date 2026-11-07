<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Http\Resource;

/**
 * The module shop: channel, catalog, quotes and orders.
 */
final class Shop extends Resource
{
    public function channel(): Channel
    {
        return Channel::fromData($this->transport->send('GET', '/shop/channel', customerToken: $this->customerToken)->data());
    }

    /**
     * The tree of active categories.
     *
     * @return list<Category>
     */
    public function categories(): array
    {
        return array_map(Category::fromData(...), $this->transport->send('GET', '/shop/categories')->data()->list('data'));
    }

    public function products(): Products
    {
        return new Products($this->transport, $this->customerToken);
    }

    /**
     * A page of changes of the catalog since the until of the last sync;
     * without since every visible entry as Updated.
     *
     * @throws \Avaro\Sdk\Exception\Gone since is too old: sync again without it
     */
    public function changes(?string $since = null, ?string $cursor = null, ?int $limit = null): ChangePage
    {
        $data = $this->transport->send('GET', '/shop/changes', ['since' => $since, 'cursor' => $cursor, 'limit' => $limit])->data();

        return new ChangePage(array_map(CatalogChange::fromData(...), $data->list('data')), $data->optString('next'), $data->optString('until'));
    }

    public function quotes(): Quotes
    {
        return new Quotes($this->transport, $this->customerToken);
    }

    public function orders(): ShopOrders
    {
        return new ShopOrders($this->transport, $this->customerToken);
    }
}
