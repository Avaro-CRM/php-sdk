<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

use Throwable;

/**
 * A response the SDK cannot read, or a status it does not expect.
 */
final class UnexpectedResponse extends AvaroException
{
    public function __construct(string $message, public readonly int $status = 0, public readonly ?Problem $problem = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }
}
