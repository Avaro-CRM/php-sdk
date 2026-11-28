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
 * provisionContact() does the same for the contact persons of a company.
 */
final class Addresses extends Resource
{
    /**
     * With additionalFields, the address carries its additional fields (e.g.
     * KAM-Fit); with contacts, its contact persons from the CRM (both can be
     * combined).
     *
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function get(string $system, string $externalId, bool $additionalFields = false, bool $contacts = false): Address
    {
        return Address::fromData($this->transport->send('GET', self::path($system, $externalId), self::expand($additionalFields, $contacts))->data());
    }

    /**
     * Sets the target account of a company: true, false or null (not
     * specified). Acts for a user: call it on asUser(), with a client that
     * has company.addresses:write. Avaro checks the user's right to edit
     * addresses and writes the change to the history in the user's name.
     *
     * @throws \Avaro\Sdk\Exception\Unauthorized     without a user token (user-token-required)
     * @throws \Avaro\Sdk\Exception\Forbidden        with slug address-not-editable, foreign-system or missing-scope
     * @throws \Avaro\Sdk\Exception\NotFound
     * @throws \Avaro\Sdk\Exception\Conflict         with slug address-deleted or address-missing
     * @throws \Avaro\Sdk\Exception\ValidationFailed when the address is no company
     */
    public function setTargetAccount(string $system, string $externalId, ?bool $targetAccount, bool $additionalFields = false): Address
    {
        return $this->update($system, $externalId, AddressChanges::new()->targetAccount($targetAccount), $additionalFields);
    }

    /**
     * Changes the target account and additional fields of a company for a
     * user, like setTargetAccount(): call it on asUser(). Only what the
     * changes set is written; invalid values change nothing.
     *
     * @throws \Avaro\Sdk\Exception\Unauthorized     without a user token (user-token-required)
     * @throws \Avaro\Sdk\Exception\Forbidden        with slug address-not-editable, foreign-system or missing-scope
     * @throws \Avaro\Sdk\Exception\NotFound
     * @throws \Avaro\Sdk\Exception\Conflict         with slug address-deleted or address-missing
     * @throws \Avaro\Sdk\Exception\ValidationFailed with a pointer per field, e.g. /additionalFields/4
     */
    public function update(string $system, string $externalId, AddressChanges $changes, bool $additionalFields = false): Address
    {
        return Address::fromData($this->transport->send('PATCH', self::path($system, $externalId), self::expand($additionalFields), $changes->toArray())->data());
    }

    /**
     * The additional fields of addresses, in the order of Avaro's form.
     *
     * @return list<AddressFieldDefinition>
     */
    public function fields(): array
    {
        return array_map(AddressFieldDefinition::fromData(...), $this->transport->send('GET', '/company/address-fields')->data()->list('data'));
    }

    /**
     * Tags of a tag field by title, as suggestions; search is part of the title.
     *
     * @return list<AdditionalFieldTag>
     *
     * @throws \Avaro\Sdk\Exception\NotFound when the field is no tag field
     */
    public function fieldTags(string $fieldId, ?string $search = null, int $limit = 50): array
    {
        $query = ['limit' => (string) $limit] + (null === $search ? [] : ['search' => $search]);
        $tags = $this->transport->send('GET', '/company/address-fields/' . self::segment($fieldId) . '/tags', $query)->data()->list('data');

        return array_map(fn($tag) => new AdditionalFieldTag($tag->string('id'), $tag->string('title')), $tags);
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

    /**
     * Links or creates a contact person of a company by its ID in the
     * client's system, e.g. a platform user: when the ID is known, the
     * contact is answered as it is; ContactData::link() links an existing
     * contact person of the company without changing it; ContactData::named()
     * creates one with the client's Avaro user as creator. Calling it again
     * changes nothing more. system must be the client's external system,
     * for the company and the contact; the client needs company.addresses:write.
     *
     * @throws \Avaro\Sdk\Exception\Conflict         with slug contact-missing (the contact to link is no active contact of
     *                                               the company outside the trash, or the contact of the ID no longer is),
     *                                               contact-linked-elsewhere (it already has another ID of the system),
     *                                               address-deleted, address-missing or busy (try again)
     * @throws \Avaro\Sdk\Exception\Forbidden        with slug foreign-system, external-system-required, client-user-required or missing-scope
     * @throws \Avaro\Sdk\Exception\NotFound         when the company has no such external ID
     * @throws \Avaro\Sdk\Exception\ValidationFailed when a new contact has neither name nor email, or a value is invalid
     */
    public function provisionContact(string $system, string $addressExternalId, string $contactExternalId, ContactData $data): AddressContact
    {
        $path = self::path($system, $addressExternalId) . '/contacts/by-external-id/' . self::segment($system) . '/' . self::segment($contactExternalId);

        return AddressContact::fromData($this->transport->send('PUT', $path, json: $data->toArray())->data());
    }

    /** @return array<string, string> */
    private static function expand(bool $additionalFields, bool $contacts = false): array
    {
        $expand = array_keys(array_filter(['additionalFields' => $additionalFields, 'contacts' => $contacts]));

        return [] === $expand ? [] : ['expand' => implode(',', $expand)];
    }

    private static function path(string $system, string $externalId): string
    {
        return '/company/addresses/by-external-id/' . self::segment($system) . '/' . self::segment($externalId);
    }
}
