<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Model\Page;
use Generator;

/**
 * The active categories of the channel, parents before their children.
 */
final class Categories extends Resource
{
    /**
     * @return Page<Category>
     */
    public function list(?int $limit = null, ?string $cursor = null): Page
    {
        $data = $this->transport->send('GET', '/events/categories', ['limit' => $limit, 'cursor' => $cursor])->data();

        return new Page(array_map(Category::fromData(...), $data->list('data')), $data->optString('next'));
    }

    /**
     * Every category, page by page.
     *
     * @return Generator<int, Category>
     */
    public function each(int $limit = 100): Generator
    {
        $cursor = null;

        do {
            $page = $this->list($limit, $cursor);

            foreach ($page->items as $category) {
                yield $category;
            }

            $cursor = $page->next;
        } while (null !== $cursor);
    }

    /**
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function get(string $id): Category
    {
        return Category::fromData($this->transport->send('GET', '/events/categories/' . self::segment($id))->data());
    }
}
