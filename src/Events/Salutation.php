<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

enum Salutation: string
{
    case Mr = 'Mr';
    case Ms = 'Ms';
    case Diverse = 'Diverse';
}
