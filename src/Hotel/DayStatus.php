<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

/**
 * A day of the calendar from the night before and the night of it:
 * ArrivalOnly is the day a stay ends, DepartureOnly the day one begins.
 */
enum DayStatus: string
{
    case Free = 'Free';
    case Booked = 'Booked';
    case ArrivalOnly = 'ArrivalOnly';
    case DepartureOnly = 'DepartureOnly';
    case Past = 'Past';
}
