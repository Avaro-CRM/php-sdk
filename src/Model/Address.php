<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Model;

/**
 * A location of the customer address; fields may be empty.
 */
final class Address
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $company,
        public readonly ?string $addition,
        public readonly ?string $street,
        public readonly ?string $postalCode,
        public readonly ?string $city,
        public readonly ?string $country,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->string('name'), $data->optString('company'), $data->optString('addition'), $data->optString('street'), $data->optString('postalCode'), $data->optString('city'), $data->optString('country'));
    }
}
