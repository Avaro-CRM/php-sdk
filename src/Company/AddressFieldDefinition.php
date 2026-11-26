<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

use Avaro\Sdk\Model\Data;

/**
 * An additional field of addresses, to build a form: type is one of the
 * AdditionalField::TYPE constants; options are the allowed values of a
 * Select field (null otherwise). Tags of a tag field come from
 * Addresses::fieldTags().
 */
final class AddressFieldDefinition
{
    /**
     * @param list<string>|null $options
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $type,
        public readonly bool $mandatory,
        public readonly bool $onlyCompanies,
        public readonly ?array $options,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            $data->string('id'),
            $data->string('title'),
            $data->string('type'),
            $data->bool('mandatory'),
            $data->bool('onlyCompanies'),
            $data->has('options') ? $data->strings('options') : null,
        );
    }
}
