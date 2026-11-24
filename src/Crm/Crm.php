<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Crm;

use Avaro\Sdk\Http\Resource;

/**
 * The module crm. Every call acts for one user: use $avaro->asUser($id)
 * for a user of the client's external system (token exchange) or an
 * employee token as BearerToken.
 */
final class Crm extends Resource
{
    public function activities(): Activities
    {
        return new Activities($this->transport);
    }
}
