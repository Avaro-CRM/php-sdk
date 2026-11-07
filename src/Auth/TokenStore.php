<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

/**
 * Keeps a client token between requests of PHP, e.g. in a cache; the
 * SDK keeps it in memory anyway.
 */
interface TokenStore
{
    public function get(string $key): ?ClientToken;

    public function put(string $key, ClientToken $token): void;
}
