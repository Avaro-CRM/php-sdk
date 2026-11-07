<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * 429; retryAfter in seconds.
 */
final class TooManyRequests extends ApiException
{
    public function __construct(Problem $problem, public readonly ?int $retryAfter = null)
    {
        parent::__construct($problem);
    }
}
