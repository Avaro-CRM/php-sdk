<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

/**
 * The answer to a booking; repeated when the same key was sent before.
 */
final class SentBooking
{
    public function __construct(
        public readonly BookingSummary $booking,
        public readonly bool $repeated,
    ) {}
}
