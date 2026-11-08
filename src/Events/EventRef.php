<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;
use DateTimeImmutable;

/**
 * An event by ID, title and start.
 */
final class EventRef
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly DateTimeImmutable $startsAt,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->string('title'), $data->dateTime('startsAt'));
    }
}
