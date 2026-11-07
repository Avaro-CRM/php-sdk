<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

/**
 * The answer to a request; repeated when the same key was sent before.
 */
final class SentRequest
{
    public function __construct(
        public readonly RequestSummary $request,
        public readonly bool $repeated,
    ) {}
}
