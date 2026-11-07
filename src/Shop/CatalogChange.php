<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;
use DateTimeImmutable;

/**
 * A product or category that changed or left the channel.
 */
final class CatalogChange
{
    public function __construct(
        public readonly CatalogEntry $type,
        public readonly string $id,
        public readonly bool $deleted,
        public readonly DateTimeImmutable $changedAt,
    ) {}

    public static function fromData(Data $data): self
    {
        $type = CatalogEntry::tryFrom($data->string('type')) ?? throw new \Avaro\Sdk\Exception\UnexpectedResponse('Unknown type of a catalog change: ' . $data->string('type'));

        return new self($type, $data->string('id'), 'Deleted' === $data->string('change'), $data->dateTime('changedAt'));
    }
}
