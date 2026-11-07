<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

enum CatalogEntry: string
{
    case Product = 'Product';
    case Category = 'Category';
}
