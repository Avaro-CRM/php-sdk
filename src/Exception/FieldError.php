<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * One invalid field: a JSON pointer such as /lines/0/quantity.
 */
final class FieldError
{
    public function __construct(
        public readonly string $pointer,
        public readonly string $detail,
        public readonly ?string $code = null,
    ) {}
}
