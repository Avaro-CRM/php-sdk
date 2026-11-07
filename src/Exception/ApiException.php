<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * The API answered with an error.
 */
abstract class ApiException extends AvaroException
{
    public function __construct(public readonly Problem $problem)
    {
        parent::__construct(null === $problem->detail ? $problem->title : $problem->title . ': ' . $problem->detail, $problem->status);
    }

    public function status(): int
    {
        return $this->problem->status;
    }

    /**
     * The kind of error, e.g. "invalid-customer-token".
     */
    public function slug(): string
    {
        return $this->problem->slug();
    }
}
