<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Model\Page;
use DateTimeInterface;
use Generator;

/**
 * The upcoming visible events of the channel, by start.
 */
final class EventList extends Resource
{
    /**
     * @param ?string            $category  only events of this category and its subcategories
     * @param ?DateTimeInterface $from      start at or after; now when null
     * @param ?DateTimeInterface $to        start at or before
     * @param bool               $available only events open for booking with a free seat
     *
     * @return Page<Event>
     *
     * @throws \Avaro\Sdk\Exception\NotFound for an unknown category
     */
    public function list(
        ?string $category = null,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        bool $available = false,
        ?int $limit = null,
        ?string $cursor = null,
    ): Page {
        $data = $this->transport->send('GET', '/events/events', [
            'category' => $category,
            'from' => $from?->format(DateTimeInterface::RFC3339),
            'to' => $to?->format(DateTimeInterface::RFC3339),
            'available' => $available ? 'true' : null,
            'limit' => $limit,
            'cursor' => $cursor,
        ])->data();

        return new Page(array_map(Event::fromData(...), $data->list('data')), $data->optString('next'));
    }

    /**
     * Every matching event, page by page.
     *
     * @return Generator<int, Event>
     */
    public function each(
        ?string $category = null,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        bool $available = false,
        int $limit = 100,
    ): Generator {
        $cursor = null;

        do {
            $page = $this->list($category, $from, $to, $available, $limit, $cursor);

            foreach ($page->items as $event) {
                yield $event;
            }

            $cursor = $page->next;
        } while (null !== $cursor);
    }

    /**
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function get(string $id): Event
    {
        return Event::fromData($this->transport->send('GET', '/events/events/' . self::segment($id))->data());
    }
}
