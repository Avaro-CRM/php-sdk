<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;

final class PaymentMethod
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('key'), $data->string('title'));
    }
}
