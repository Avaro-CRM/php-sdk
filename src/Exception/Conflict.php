<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

use Avaro\Sdk\Events\Event;
use Avaro\Sdk\Hotel\Quote as StayQuote;
use Avaro\Sdk\Shop\Quote;

/**
 * 409, with the quote of the shop (quote), of a stay in the module hotel
 * (stayQuote) or the event of a booking in the module events (event)
 * when there is one.
 */
class Conflict extends ApiException
{
    public function __construct(Problem $problem, public readonly ?Quote $quote = null, public readonly ?StayQuote $stayQuote = null, public readonly ?Event $event = null)
    {
        parent::__construct($problem);
    }
}
