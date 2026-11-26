<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

use Avaro\Sdk\Model\Data;

/**
 * A contact field of a contact person: an email address, a phone number
 * and so on. type is one of the TYPE constants (other types may follow);
 * scope is e.g. business or private, null when not given. primary marks the
 * preferred field of its type.
 */
final class AddressContactField
{
    public const EMAIL = 'email';
    public const PHONE = 'phone';
    public const MOBILE = 'mobile';
    public const FAX = 'fax';

    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly ?string $scope,
        public readonly ?string $value,
        public readonly bool $primary = false,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            $data->string('id'),
            $data->string('type'),
            $data->optString('scope'),
            $data->optString('value'),
            $data->has('isPrimary') && $data->bool('isPrimary'),
        );
    }
}
