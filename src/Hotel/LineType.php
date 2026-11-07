<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

/**
 * Stay: nights at one price; ExtraGuests: guests beyond the included
 * ones per night; Pets per night; Fee once per stay.
 */
enum LineType: string
{
    case Stay = 'Stay';
    case ExtraGuests = 'ExtraGuests';
    case Pets = 'Pets';
    case Fee = 'Fee';
}
