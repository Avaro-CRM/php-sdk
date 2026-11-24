<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

use Avaro\Sdk\Http\Resource;

/**
 * Addresses (companies) by external ID. provision() finds the address by
 * the ID, else links the one address with the customer number, else
 * creates a company; then writes the fields set. Calling it again changes
 * nothing more. Afterwards todos find the address by the same reference.
 */
final class Addresses extends Resource
{
    /**
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function get(string $system, string $externalId): Address
    {
        return Address::fromData($this->transport->send('GET', self::path($system, $externalId))->data());
    }

    /**
     * @throws \Avaro\Sdk\Exception\Conflict         with slug customer-number-ambiguous, customer-number-taken,
     *                                               address-linked-elsewhere, address-deleted, address-missing or busy
     * @throws \Avaro\Sdk\Exception\Forbidden        when system is not the client's external system or the client acts as no Avaro user
     * @throws \Avaro\Sdk\Exception\ValidationFailed
     */
    public function provision(string $system, string $externalId, AddressData $data): Address
    {
        $result = $this->transport->send('PUT', self::path($system, $externalId), json: $data->toArray());

        return Address::fromData($result->data(), 201 === $result->status);
    }

    private static function path(string $system, string $externalId): string
    {
        return '/company/addresses/by-external-id/' . self::segment($system) . '/' . self::segment($externalId);
    }
}
