<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

/**
 * The value of a field: Checkbox true or false, Text a string, Choice the
 * value of an option.
 */
enum FieldType: string
{
    case Checkbox = 'Checkbox';
    case Text = 'Text';
    case Choice = 'Choice';
}
