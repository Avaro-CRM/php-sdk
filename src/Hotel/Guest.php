<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use InvalidArgumentException;

/**
 * The guest of a request. Avaro creates a new address for them.
 */
final class Guest
{
    public function __construct(
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $firstName = null,
        public readonly ?Salutation $salutation = null,
        public readonly ?string $street = null,
        public readonly ?string $houseNumber = null,
        public readonly ?string $addition = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $city = null,
        public readonly ?string $country = null,
        public readonly ?string $phone = null,
    ) {
        if ('' === trim($lastName) || '' === trim($email)) {
            throw new InvalidArgumentException('A guest needs a last name and an email address.');
        }
    }

    /**
     * @return array<string, ?string>
     */
    public function toArray(): array
    {
        return [
            'salutation' => $this->salutation?->value,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'street' => $this->street,
            'houseNumber' => $this->houseNumber,
            'addition' => $this->addition,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
