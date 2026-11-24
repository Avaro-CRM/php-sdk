<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

/**
 * The answer of create(): created is false when an open todo already held
 * the sourceKey and was returned instead.
 */
final class CreatedTodo
{
    public function __construct(
        public readonly Todo $todo,
        public readonly bool $created,
    ) {}
}
