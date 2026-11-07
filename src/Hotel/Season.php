<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use DateTimeImmutable;

/**
 * A season from from to to, both included.
 */
final class Season
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $title,
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->optString('title'), $data->dateTime('from'), $data->dateTime('to'));
    }
}
