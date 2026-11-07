<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

use Avaro\Sdk\Shop\Quote;

/**
 * 409, with the quote of the shop when there is one.
 */
class Conflict extends ApiException
{
    public function __construct(Problem $problem, public readonly ?Quote $quote = null)
    {
        parent::__construct($problem);
    }
}
