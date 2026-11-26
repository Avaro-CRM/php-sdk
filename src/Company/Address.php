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
 *
 * targetAccount is true or false for a company and null when it is not
 * specified or the address is no company; provision() never changes it, see
 * Addresses::setTargetAccount(). additionalFields is null unless they were
 * requested (Addresses::get(..., additionalFields: true)); so are contacts,
 * the contact persons from the CRM (Addresses::get(..., contacts: true)).
 */
final class Address
{
    public const ACTIVE = 'Active';
    public const INACTIVE = 'Inactive';
    public const DELETED = 'Deleted';
    public const PROVISIONED = 'Provisioned';
    public const LINKED = 'Linked';

    /**
     * @param list<ExternalReference>    $externalIds
     * @param list<AdditionalField>|null $additionalFields
     * @param list<AddressContact>|null  $contacts
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
        public readonly ?bool $targetAccount = null,
        public readonly ?array $additionalFields = null,
        public readonly ?array $contacts = null,
    ) {}

    /** The additional field with this title, e.g. 'KAM-Fit'; null when absent or not requested. */
    public function additionalField(string $title): ?AdditionalField
    {
        foreach ($this->additionalFields ?? [] as $field) {
            if ($field->title === $title) {
                return $field;
            }
        }

        return null;
    }

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
            self::optBool($data, 'targetAccount'),
            null === ($fields = $data->optList('additionalFields')) ? null : array_map(AdditionalField::fromData(...), $fields),
            null === ($contacts = $data->optList('contacts')) ? null : array_map(AddressContact::fromData(...), $contacts),
        );
    }

    private static function optBool(Data $data, string $key): ?bool
    {
        return $data->has($key) ? $data->bool($key) : null;
    }
}
