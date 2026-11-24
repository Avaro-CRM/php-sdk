<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use Avaro\Sdk\Model\Data;

/**
 * A user of the tenant: assignee, creator or one that can be assigned.
 */
final class UserRef
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

    /** The ID of the user in this system, null when it has none. */
    public function referenceOf(string $system): ?string
    {
        foreach ($this->references as $reference) {
            if ($reference->system === $system) {
                return $reference->externalId;
            }
        }

        return null;
    }
}
