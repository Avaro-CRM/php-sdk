<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Model\Page;
use DateTimeInterface;
use Generator;

/**
 * The units of the channel: holiday apartments or room types.
 */
final class Units extends Resource
{
    /**
     * @return Page<Unit>
     */
    public function list(?int $limit = null, ?string $cursor = null): Page
    {
        $data = $this->transport->send('GET', '/hotel/units', ['limit' => $limit, 'cursor' => $cursor])->data();

        return new Page(array_map(Unit::fromData(...), $data->list('data')), $data->optString('next'));
    }

    /**
     * Every unit, page by page.
     *
     * @return Generator<int, Unit>
     */
    public function each(int $limit = 100): Generator
    {
        $cursor = null;

        do {
            $page = $this->list($limit, $cursor);

            foreach ($page->items as $unit) {
                yield $unit;
            }

            $cursor = $page->next;
        } while (null !== $cursor);
    }

    /**
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function get(string $id): Unit
    {
        return Unit::fromData($this->transport->send('GET', '/hotel/units/' . self::segment($id))->data());
    }

    /**
     * The days from from to to, at most 400.
     *
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function calendar(string $id, DateTimeInterface $from, DateTimeInterface $to): Calendar
    {
        return Calendar::fromData($this->transport->send('GET', '/hotel/units/' . self::segment($id) . '/calendar', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')])->data());
    }
}
