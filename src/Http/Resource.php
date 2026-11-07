<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Http;

use LogicException;

/**
 * The base of every part of the API.
 *
 * @internal
 */
abstract class Resource
{
    public function __construct(
        protected readonly Transport $transport,
        protected readonly ?string $customerToken = null,
    ) {}

    /**
     * The customer token, which this call needs.
     */
    protected function customer(): string
    {
        return $this->customerToken ?? throw new LogicException('This call acts for a signed-in contact: use $avaro->withCustomer($token) first.');
    }

    protected static function segment(string $value): string
    {
        return rawurlencode($value);
    }
}
