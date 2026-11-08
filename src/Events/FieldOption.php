<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;

final class FieldOption
{
    public function __construct(
        public readonly string $value,
        public readonly string $title,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('value'), $data->string('title'));
    }
}
