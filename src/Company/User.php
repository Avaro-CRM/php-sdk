<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Todos\ExternalReference;

/**
 * A user of the tenant. status is Active or Inactive (membership removed or
 * blocked in Avaro); origin is Provisioned (created by the client, which
 * keeps the profile) or Linked (an existing Avaro user that keeps its own
 * profile). created is true when the call created the user.
 */
final class User
{
    public const ACTIVE = 'Active';
    public const INACTIVE = 'Inactive';
    public const PROVISIONED = 'Provisioned';
    public const LINKED = 'Linked';

    /**
     * @param list<ExternalReference> $externalIds
     */
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $status,
        public readonly string $origin,
        public readonly array $externalIds,
        public readonly bool $created = false,
    ) {}

    public static function fromData(Data $data, bool $created = false): self
    {
        return new self($data->string('id'), $data->string('email'), $data->string('firstName'), $data->string('lastName'), $data->string('status'), $data->string('origin'), ExternalReference::list($data->list('externalIds')), $created);
    }

    public function isActive(): bool
    {
        return self::ACTIVE === $this->status;
    }
}
