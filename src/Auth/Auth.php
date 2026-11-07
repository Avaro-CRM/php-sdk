<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use Avaro\Sdk\Http\Resource;

/**
 * The part auth: sign-in of contacts.
 */
final class Auth extends Resource
{
    public function customers(): Customers
    {
        return new Customers($this->transport, $this->customerToken);
    }
}
