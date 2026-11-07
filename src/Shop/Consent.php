<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

/**
 * Agreement to a legal text in the version shown.
 */
final class Consent
{
    public function __construct(
        public readonly string $key,
        public readonly string $version,
    ) {}

    public static function to(LegalText $text): self
    {
        return new self($text->key, $text->version);
    }
}
