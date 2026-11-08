<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

/**
 * A detail a booking of the channel must contain: Address (street, house
 * number, postal code and city of the customer), Phone (of the customer),
 * DateOfBirth (of every attendee).
 */
enum RequiredDetail: string
{
    case Address = 'Address';
    case Phone = 'Phone';
    case DateOfBirth = 'DateOfBirth';
}
