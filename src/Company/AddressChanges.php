<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

/**
 * Changes of a company that a user makes, for Addresses::update(). Only
 * what is set changes; field IDs come from Addresses::fields().
 *
 *     $changes = AddressChanges::new()
 *         ->targetAccount(true)
 *         ->tags($kamFitId, existing: ['2528'], new: ['Neuer Kunde'])
 *         ->value($branchId, 'Bau')
 *         ->clear($employeesId);
 */
final class AddressChanges
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(private readonly array $values = []) {}

    public static function new(): self
    {
        return new self();
    }

    /** true, false or null (not specified). */
    public function targetAccount(?bool $targetAccount): self
    {
        return new self(['targetAccount' => $targetAccount] + $this->values);
    }

    /**
     * The value of a field that is no tag field: a string for text, email,
     * phone, date (YYYY-MM-DD) and selection, a number or numeric string for
     * Number, a bool for Checkbox; null clears it.
     */
    public function value(string $fieldId, string|int|float|bool|null $value): self
    {
        return $this->field($fieldId, $value);
    }

    /** Clears a field: an empty text, false for a checkbox, no tags. */
    public function clear(string $fieldId): self
    {
        return $this->field($fieldId, null);
    }

    /**
     * The tags of a tag field, replacing the ones it has: IDs of existing
     * tags (Addresses::fieldTags()) and titles to find or create.
     *
     * @param list<string> $existing
     * @param list<string> $new
     */
    public function tags(string $fieldId, array $existing = [], array $new = []): self
    {
        return $this->field($fieldId, [
            ...array_map(fn(string $id) => ['id' => $id], $existing),
            ...array_map(fn(string $title) => ['title' => $title], $new),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Field IDs are digits: an object keeps them keys, never a JSON list.
        return isset($this->values['additionalFields']) ? ['additionalFields' => (object) $this->values['additionalFields']] + $this->values : $this->values;
    }

    private function field(string $fieldId, mixed $value): self
    {
        $values = $this->values;
        /** @var array<string, mixed> $fields */
        $fields = $values['additionalFields'] ?? [];
        $fields[$fieldId] = $value;
        $values['additionalFields'] = $fields;

        return new self($values);
    }
}
