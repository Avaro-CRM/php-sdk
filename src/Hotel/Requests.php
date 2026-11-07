<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Http\Resource;
use InvalidArgumentException;

/**
 * Sends booking requests; the tenant confirms or declines them in Avaro
 * and Avaro writes to the guest.
 */
final class Requests extends Resource
{
    /**
     * The same key with the same body returns the request stored before.
     *
     * @param string $idempotencyKey printable ASCII, 8 to 128 characters, one per attempt
     *
     * @throws \Avaro\Sdk\Exception\TotalChanged        with the new quote in stayQuote
     * @throws \Avaro\Sdk\Exception\QuoteInvalid        with the quote and its problems in stayQuote
     * @throws \Avaro\Sdk\Exception\IdempotencyConflict
     * @throws \Avaro\Sdk\Exception\ValidationFailed    e.g. ConsentMissing
     */
    public function send(BookingRequest $request, string $idempotencyKey): SentRequest
    {
        if (1 !== preg_match('/^[\x21-\x7E]{8,128}$/', $idempotencyKey)) {
            throw new InvalidArgumentException('An idempotency key is printable ASCII with 8 to 128 characters.');
        }

        $result = $this->transport->send('POST', '/hotel/requests', json: $request->toArray(), headers: ['Idempotency-Key' => $idempotencyKey]);

        return new SentRequest(RequestSummary::fromData($result->data()), 200 === $result->status);
    }
}
