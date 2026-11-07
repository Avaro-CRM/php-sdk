<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Webhooks;

use DateTimeImmutable;

/**
 * An event such as product.updated or order.status-changed; data has at
 * least id.
 */
final class WebhookEvent
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly DateTimeImmutable $timestamp,
        public readonly array $data,
    ) {}
}
