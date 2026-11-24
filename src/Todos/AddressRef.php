<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use Avaro\Sdk\Model\Data;

/**
 * The address (company or person) of a todo.
 */
final class AddressRef
{
    /**
     * @param list<ExternalReference> $references
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $customerNumber,
        public readonly array $references,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->string('name'), $data->optString('customerNumber'), ExternalReference::list($data->optList('references') ?? []));
    }
}
