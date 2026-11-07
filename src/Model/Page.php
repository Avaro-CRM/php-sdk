<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Model;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * One page of a list; next is the cursor of the following page, null on
 * the last one.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
final class Page implements IteratorAggregate, Countable
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $next,
    ) {}

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }
}
