<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use Avaro\Sdk\Model\Data;

/**
 * The contact person of a todo; belongs to its address.
 */
final class ContactRef
{
    /**
     * @param list<ExternalReference> $references
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $references,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->string('name'), ExternalReference::list($data->optList('references') ?? []));
    }
}
