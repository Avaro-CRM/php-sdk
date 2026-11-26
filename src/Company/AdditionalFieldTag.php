<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

/**
 * A selected tag of an additional field of type Tags.
 */
final class AdditionalFieldTag
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
    ) {}
}
