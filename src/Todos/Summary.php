<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use Avaro\Sdk\Model\Data;

/**
 * Counters of open todos, e.g. for a dashboard; today is the reference
 * date used.
 */
final class Summary
{
    public function __construct(
        public readonly string $today,
        public readonly int $open,
        public readonly int $dueToday,
        public readonly int $overdue,
        public readonly int $highPriority,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('today'), $data->int('open'), $data->int('dueToday'), $data->int('overdue'), $data->int('highPriority'));
    }
}
