<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Model;

/**
 * A price per unit as decimal strings with four places, never a float.
 */
final class UnitPrice
{
    public function __construct(
        public readonly string $net,
        public readonly string $gross,
        public readonly string $taxRate,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('net'), $data->string('gross'), $data->string('taxRate'));
    }
}
