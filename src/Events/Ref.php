<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;

/**
 * A category by ID and title.
 */
final class Ref
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->string('title'));
    }
}
