<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Model;

/**
 * An address as written on an order; fields may be empty.
 */
final class PostalAddress
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $company,
        public readonly ?string $addition,
        public readonly ?string $street,
        public readonly ?string $postalCode,
        public readonly ?string $city,
        public readonly ?string $country,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->optString('name'), $data->optString('company'), $data->optString('addition'), $data->optString('street'), $data->optString('postalCode'), $data->optString('city'), $data->optString('country'));
    }
}
