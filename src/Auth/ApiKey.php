<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use Avaro\Sdk\Http\Transport;
use Psr\Http\Message\RequestInterface;

/**
 * The key of a client, avk_<clientId>_<secret>, in X-API-KEY.
 */
final class ApiKey implements Credentials
{
    public function __construct(#[\SensitiveParameter] private readonly string $key) {}

    public function authenticate(RequestInterface $request, Transport $transport): RequestInterface
    {
        return $request->withHeader('X-API-KEY', $this->key);
    }
}
