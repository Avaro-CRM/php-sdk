<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use Avaro\Sdk\Http\Transport;
use Psr\Http\Message\RequestInterface;

/**
 * A bearer token the caller already has, e.g. avt_… from elsewhere.
 */
final class BearerToken implements Credentials
{
    public function __construct(#[\SensitiveParameter] private readonly string $token) {}

    public function authenticate(RequestInterface $request, Transport $transport): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->token);
    }
}
