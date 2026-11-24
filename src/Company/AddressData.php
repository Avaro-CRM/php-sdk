<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

/**
 * Master data of a company to provision. Only the name is required; a field
 * that is not set stays as it is in Avaro, null clears it. The customer
 * number links an existing address the first time and cannot be cleared.
 *
 *     $data = AddressData::named('Muster Bau GmbH')
 *         ->customerNumber('10042')
 *         ->location('Hauptstraße', '1', '26122', 'Oldenburg', 'DE')
 *         ->email('info@muster-bau.example');
 */
final class AddressData
{
    /**
     * @param array<string, string|null> $fields
     */
    private function __construct(private readonly string $name, private readonly array $fields = []) {}

    public static function named(string $name): self
    {
        return new self($name);
    }

    public function customerNumber(string $customerNumber): self
    {
        return $this->with('customerNumber', $customerNumber);
    }

    public function email(?string $email): self
    {
        return $this->with('email', $email);
    }

    public function phone(?string $phone): self
    {
        return $this->with('phone', $phone);
    }

    public function website(?string $website): self
    {
        return $this->with('website', $website);
    }

    /**
     * The main location; country is an ISO 3166-1 alpha-2 code such as DE.
     */
    public function location(?string $street, ?string $streetNumber, ?string $zipcode, ?string $city, ?string $country = null): self
    {
        return $this->with('street', $street)->with('streetNumber', $streetNumber)->with('zipcode', $zipcode)->with('city', $city)->with('country', $country);
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return ['name' => $this->name] + $this->fields;
    }

    private function with(string $field, ?string $value): self
    {
        return new self($this->name, [$field => $value] + $this->fields);
    }
}
