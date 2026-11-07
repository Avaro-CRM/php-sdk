<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

/**
 * Which price a shop shows first.
 */
enum PriceDisplay: string
{
    case Net = 'Net';
    case Gross = 'Gross';
}
