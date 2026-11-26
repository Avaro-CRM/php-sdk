<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

use Avaro\Sdk\Exception\UnexpectedResponse;
use Avaro\Sdk\Model\Data;

/**
 * An additional field of an address, e.g. KAM-Fit; only with
 * Addresses::get(..., additionalFields: true). type is one of the TYPE
 * constants. stored tells whether a value was saved; a field without one
 * has null (or [] for tags), never a default.
 *
 * value: a string for text, number (exact), date and selection; a bool for
 * Checkbox; null when nothing is stored; for Tags the list of selected tags.
 */
final class AdditionalField
{
    public const TEXT = 'Text';
    public const TEXTAREA = 'Textarea';
    public const NUMBER = 'Number';
    public const DATE = 'Date';
    public const CHECKBOX = 'Checkbox';
    public const SELECT = 'Select';
    public const EMAIL = 'Email';
    public const PHONE = 'Phone';
    public const TAGS = 'Tags';

    /**
     * @param string|bool|list<AdditionalFieldTag>|null $value
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $type,
        public readonly bool $stored,
        public readonly string|bool|array|null $value,
    ) {}

    /**
     * The selected tags; empty for fields of another type.
     *
     * @return list<AdditionalFieldTag>
     */
    public function tags(): array
    {
        return \is_array($this->value) ? $this->value : [];
    }

    public static function fromData(Data $data): self
    {
        $value = $data->values['value'] ?? null;

        if (\is_array($value)) {
            $value = array_map(fn(Data $tag) => new AdditionalFieldTag($tag->string('id'), $tag->string('title')), $data->list('value'));
        } elseif (null !== $value && !\is_string($value) && !\is_bool($value)) {
            throw new UnexpectedResponse(\sprintf('The value of the additional field "%s" is not a string, boolean, list or null.', $data->string('id')));
        }

        return new self($data->string('id'), $data->string('title'), $data->string('type'), $data->bool('stored'), $value);
    }
}
