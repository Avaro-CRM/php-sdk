<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\UnitPrice;

/**
 * A fee every stay pays once, e.g. final cleaning, with what it includes.
 */
final class Fee
{
    /**
     * @param list<string> $includes
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly UnitPrice $price,
        public readonly array $includes,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('key'), $data->string('title'), UnitPrice::fromData($data->data('price')), $data->strings('includes'));
    }
}
