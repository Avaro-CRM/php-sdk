<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

use Avaro\Sdk\Http\Resource;

/**
 * The module company: users of the tenant by their ID in the client's
 * external system.
 */
final class Company extends Resource
{
    public function users(): Users
    {
        return new Users($this->transport);
    }
}
