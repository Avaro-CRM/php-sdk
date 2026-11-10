<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

/**
 * A detail a request of the channel must contain beyond last name and
 * email: Salutation, FirstName, Address (street, house number, postal
 * code and city) and Phone.
 */
enum RequiredDetail: string
{
    case Salutation = 'Salutation';
    case FirstName = 'FirstName';
    case Address = 'Address';
    case Phone = 'Phone';
}
