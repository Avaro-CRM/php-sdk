<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

/**
 * A page of catalog changes: next for the following page, until on the
 * last page for the next sync.
 */
final class ChangePage
{
    /**
     * @param list<CatalogChange> $changes
     */
    public function __construct(
        public readonly array $changes,
        public readonly ?string $next,
        public readonly ?string $until,
    ) {}
}
