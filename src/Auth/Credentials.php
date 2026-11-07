<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use Avaro\Sdk\Http\Transport;
use Psr\Http\Message\RequestInterface;

/**
 * How the client authenticates: ApiKey, BasicAuth, ClientCredentials or
 * BearerToken.
 */
interface Credentials
{
    public function authenticate(RequestInterface $request, Transport $transport): RequestInterface;
}
