<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

enum AvailabilityStatus: string
{
    case Available = 'Available';
    case LowStock = 'LowStock';
    case OnOrder = 'OnOrder';
    case OutOfStock = 'OutOfStock';
    case Unknown = 'Unknown';
}
