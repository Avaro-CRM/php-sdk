<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use Avaro\Sdk\Http\Transport;
use Psr\Http\Message\RequestInterface;

/**
 * Client ID and secret with every request, by HTTP Basic.
 */
final class BasicAuth implements Credentials
{
    public function __construct(
        private readonly string $clientId,
        #[\SensitiveParameter] private readonly string $secret,
    ) {}

    public function authenticate(RequestInterface $request, Transport $transport): RequestInterface
    {
        return $request->withHeader('Authorization', 'Basic ' . base64_encode($this->clientId . ':' . $this->secret));
    }
}
