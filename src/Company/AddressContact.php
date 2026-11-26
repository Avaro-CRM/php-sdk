<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

use Avaro\Sdk\Model\Data;

/**
 * A contact person of an address in the CRM; only with
 * Addresses::get(..., contacts: true). Only active contacts outside the
 * trash are listed. id is the public ID of the contact in Avaro; position is
 * the job position, e.g. Geschäftsführung.
 */
final class AddressContact
{
    /**
     * @param list<AddressContactField> $fields
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $title,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $position,
        public readonly array $fields = [],
    ) {}

    /** First and last name, e.g. "Erika Muster"; the title is left out. Empty when neither is known. */
    public function name(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    /** The primary email address, else the first one; null without any. */
    public function email(): ?string
    {
        return $this->preferred(AddressContactField::EMAIL);
    }

    /** The primary phone number, else the first one, else the primary or first mobile number; null without any. */
    public function phone(): ?string
    {
        return $this->preferred(AddressContactField::PHONE) ?? $this->preferred(AddressContactField::MOBILE);
    }

    /**
     * The fields of a type, e.g. AddressContactField::EMAIL.
     *
     * @return list<AddressContactField>
     */
    public function fieldsOfType(string $type): array
    {
        return array_values(array_filter($this->fields, fn(AddressContactField $field) => $field->type === $type));
    }

    public static function fromData(Data $data): self
    {
        return new self(
            $data->string('id'),
            $data->optString('title'),
            $data->optString('firstName'),
            $data->optString('lastName'),
            $data->optString('position'),
            array_map(AddressContactField::fromData(...), $data->optList('fields') ?? []),
        );
    }

    private function preferred(string $type): ?string
    {
        $fields = array_filter($this->fieldsOfType($type), fn(AddressContactField $field) => null !== $field->value && '' !== $field->value);

        foreach ($fields as $field) {
            if ($field->primary) {
                return $field->value;
            }
        }

        $first = reset($fields);

        return false === $first ? null : $first->value;
    }
}
