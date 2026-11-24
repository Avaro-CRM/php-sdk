<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

/**
 * An address by its ID in another system, with its customer number as
 * fallback: while the ID is not linked yet, Avaro uses the one address
 * with this number and, for the client's own system, links the ID to it
 * when a todo is created or changed.
 */
final class AddressReference
{
    public function __construct(
        public readonly string $system,
        public readonly string $externalId,
        public readonly ?string $customerNumber = null,
    ) {}

    public function reference(): ExternalReference
    {
        return new ExternalReference($this->system, $this->externalId);
    }
}
