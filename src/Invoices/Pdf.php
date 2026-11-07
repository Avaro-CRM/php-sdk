<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Invoices;

/**
 * The PDF of an invoice.
 */
final class Pdf
{
    public function __construct(
        public readonly string $fileName,
        public readonly string $contents,
    ) {}
}
