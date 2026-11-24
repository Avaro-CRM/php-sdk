<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Todos\ExternalReference;

/**
 * An address (company) of the tenant; id is the same as the address of a
 * todo. status is Active, Inactive or Deleted (in the trash, not writable);
 * origin is Provisioned (created by the client) or Linked (an existing
 * address, e.g. found by customer number). created is true when the call
 * created the address.
 */
final class Address
{
    public const ACTIVE = 'Active';
    public const INACTIVE = 'Inactive';
    public const DELETED = 'Deleted';
    public const PROVISIONED = 'Provisioned';
    public const LINKED = 'Linked';

    /**
     * @param list<ExternalReference> $externalIds
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $customerNumber,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $website,
        public readonly ?string $street,
        public readonly ?string $streetNumber,
        public readonly ?string $zipcode,
        public readonly ?string $city,
        public readonly ?string $country,
        public readonly string $status,
        public readonly string $origin,
        public readonly array $externalIds,
        public readonly bool $created = false,
    ) {}

    public static function fromData(Data $data, bool $created = false): self
    {
        return new self(
            $data->string('id'),
            $data->string('name'),
            $data->optString('customerNumber'),
            $data->optString('email'),
            $data->optString('phone'),
            $data->optString('website'),
            $data->optString('street'),
            $data->optString('streetNumber'),
            $data->optString('zipcode'),
            $data->optString('city'),
            $data->optString('country'),
            $data->string('status'),
            $data->string('origin'),
            ExternalReference::list($data->list('externalIds')),
            $created,
        );
    }
}
