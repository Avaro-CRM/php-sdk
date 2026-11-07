<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Model;

/**
 * Net, tax and gross in integer cents.
 */
final class Totals
{
    public function __construct(
        public readonly int $net,
        public readonly int $tax,
        public readonly int $gross,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->int('net'), $data->int('tax'), $data->int('gross'));
    }
}
