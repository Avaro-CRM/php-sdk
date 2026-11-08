<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

/**
 * Whether an event can be booked online now. Open: with at least one free
 * seat. SoldOut: no free seat. NotYetOpen: the booking window starts
 * later. Closed: the window has ended or the event has started. Offline:
 * only by phone or at a booking office.
 */
enum BookingStatus: string
{
    case Open = 'Open';
    case SoldOut = 'SoldOut';
    case NotYetOpen = 'NotYetOpen';
    case Closed = 'Closed';
    case Offline = 'Offline';
}
