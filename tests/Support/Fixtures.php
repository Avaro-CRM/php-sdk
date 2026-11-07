<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests\Support;

use RuntimeException;

/**
 * Responses as in the examples of the API, in tests/fixtures/.
 */
final class Fixtures
{
    /**
     * @return array<mixed>
     */
    public static function load(string $name): array
    {
        $file = __DIR__ . '/../fixtures/' . $name . '.json';
        $decoded = json_decode((string) file_get_contents($file), true, 64, \JSON_THROW_ON_ERROR);

        return \is_array($decoded) ? $decoded : throw new RuntimeException('No fixture ' . $name);
    }
}
