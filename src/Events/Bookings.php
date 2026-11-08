<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Http\Resource;
use InvalidArgumentException;

/**
 * Books events; Avaro checks the seats, stores the booking and writes to
 * the customer.
 */
final class Bookings extends Resource
{
    /**
     * The same key with the same body returns the booking stored before.
     *
     * @param string $idempotencyKey printable ASCII, 8 to 128 characters, one per attempt
     *
     * @throws \Avaro\Sdk\Exception\SoldOut             not enough free seats; the event as it is now in event
     * @throws \Avaro\Sdk\Exception\NotBookable         not open for booking; the event in event
     * @throws \Avaro\Sdk\Exception\TotalChanged        the price changed; the event in event
     * @throws \Avaro\Sdk\Exception\IdempotencyConflict
     * @throws \Avaro\Sdk\Exception\ValidationFailed    e.g. ConsentMissing, FieldRequired, TooManyAttendees
     * @throws \Avaro\Sdk\Exception\NotFound            the event is not in the channel
     */
    public function book(Booking $booking, string $idempotencyKey): SentBooking
    {
        if (1 !== preg_match('/^[\x21-\x7E]{8,128}$/', $idempotencyKey)) {
            throw new InvalidArgumentException('An idempotency key is printable ASCII with 8 to 128 characters.');
        }

        $result = $this->transport->send('POST', '/events/bookings', json: $booking->toArray(), headers: ['Idempotency-Key' => $idempotencyKey]);

        return new SentBooking(BookingSummary::fromData($result->data()), 200 === $result->status);
    }
}
