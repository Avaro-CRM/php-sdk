<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

/**
 * How to address the contact, as set in Avaro: ByThem formal (German
 * "Sie"), ByYou informal ("Du").
 */
enum PersonalApproach: string
{
    case ByThem = 'ByThem';
    case ByYou = 'ByYou';
}
