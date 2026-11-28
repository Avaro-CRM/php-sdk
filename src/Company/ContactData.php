<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

/**
 * A contact person to link or create with Addresses::provisionContact().
 * link() names an existing contact person of the address by its Avaro ID
 * (AddressContact::$id); none of its fields is changed. named() describes
 * a new one: it needs a first or last name or an email; email, phone and
 * mobile become its primary contact fields.
 *
 *     $data = ContactData::named('Erika', 'Muster')->title('Dr.')->email('erika@muster-bau.example');
 *     $data = ContactData::link($contact->id);
 */
final class ContactData
{
    /**
     * @param array<string, string|null> $fields
     */
    private function __construct(private readonly ?string $contactId, private readonly array $fields = []) {}

    public static function link(string $contactId): self
    {
        return new self($contactId);
    }

    public static function named(?string $firstName, ?string $lastName): self
    {
        return new self(null, ['firstName' => $firstName, 'lastName' => $lastName]);
    }

    /** Academic title, e.g. Dr. */
    public function title(?string $title): self
    {
        return $this->with('title', $title);
    }

    public function email(?string $email): self
    {
        return $this->with('email', $email);
    }

    public function phone(?string $phone): self
    {
        return $this->with('phone', $phone);
    }

    public function mobile(?string $mobile): self
    {
        return $this->with('mobile', $mobile);
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return null === $this->contactId ? $this->fields : ['contactId' => $this->contactId] + $this->fields;
    }

    private function with(string $field, ?string $value): self
    {
        return new self($this->contactId, [$field => $value] + $this->fields);
    }
}
