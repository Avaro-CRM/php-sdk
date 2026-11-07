<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * An error of the API as RFC 9457 problem.
 */
final class Problem
{
    /**
     * @param array<string, mixed> $raw the whole body
     */
    public function __construct(
        public readonly int $status,
        public readonly string $type,
        public readonly string $title,
        public readonly ?string $detail = null,
        public readonly ?string $diagnosticId = null,
        public readonly array $raw = [],
    ) {}

    /**
     * The last part of the type, e.g. "total-changed" or "invalid-login".
     */
    public function slug(): string
    {
        $path = parse_url($this->type, \PHP_URL_PATH);
        $path = \is_string($path) && '' !== $path ? $path : $this->type;
        $parts = explode('/', rtrim($path, '/'));

        return (string) end($parts);
    }
}
