<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Http;

use Avaro\Sdk\Exception\AvaroException;
use Avaro\Sdk\Exception\BadRequest;
use Avaro\Sdk\Exception\Conflict;
use Avaro\Sdk\Exception\FieldError;
use Avaro\Sdk\Exception\Forbidden;
use Avaro\Sdk\Exception\Gone;
use Avaro\Sdk\Exception\IdempotencyConflict;
use Avaro\Sdk\Exception\NotBookable;
use Avaro\Sdk\Exception\NotFound;
use Avaro\Sdk\Exception\Problem;
use Avaro\Sdk\Exception\QuoteInvalid;
use Avaro\Sdk\Exception\ServerError;
use Avaro\Sdk\Exception\SoldOut;
use Avaro\Sdk\Exception\TooManyRequests;
use Avaro\Sdk\Exception\TotalChanged;
use Avaro\Sdk\Exception\Unauthorized;
use Avaro\Sdk\Exception\UnexpectedResponse;
use Avaro\Sdk\Exception\ValidationFailed;
use Avaro\Sdk\Events\Event;
use Avaro\Sdk\Hotel\Quote as StayQuote;
use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Shop\Quote;
use Throwable;

/**
 * Turns an error response into an exception.
 *
 * @internal
 */
final class Errors
{
    public static function fromResult(Result $result): AvaroException
    {
        try {
            $data = $result->data();
        } catch (UnexpectedResponse) {
            $data = new Data([]);
        }

        $problem = self::problem($result, $data);

        return match (true) {
            400 === $result->status => new BadRequest($problem),
            401 === $result->status => new Unauthorized($problem),
            403 === $result->status => new Forbidden($problem),
            404 === $result->status => new NotFound($problem),
            409 === $result->status => self::conflict($problem, $data),
            410 === $result->status => new Gone($problem),
            422 === $result->status => new ValidationFailed($problem, self::fieldErrors($data)),
            429 === $result->status => new TooManyRequests($problem, self::retryAfter($result)),
            $result->status >= 500 => new ServerError($problem),
            default => new UnexpectedResponse(\sprintf('The API answered %d.', $result->status), $result->status, $problem),
        };
    }

    private static function problem(Result $result, Data $data): Problem
    {
        // OAuth errors of /auth/token come as {error, error_description}.
        $oauth = $data->optString('error');

        return new Problem(
            $result->status,
            $data->optString('type') ?? (null === $oauth ? 'about:blank' : 'oauth:' . $oauth),
            $data->optString('title') ?? $oauth ?? ('HTTP ' . $result->status),
            $data->optString('detail') ?? $data->optString('error_description'),
            $data->optString('diagnosticId'),
            $data->values,
        );
    }

    private static function conflict(Problem $problem, Data $data): Conflict
    {
        $quote = null;
        $stayQuote = null;

        try {
            $quoteData = $data->optData('quote');

            // A quote of a stay has nights, one of the shop goods.
            if (null !== $quoteData && $quoteData->has('nights')) {
                $stayQuote = StayQuote::fromData($quoteData);
            } elseif (null !== $quoteData) {
                $quote = Quote::fromData($quoteData);
            }
        } catch (Throwable) {
            $quote = null;
            $stayQuote = null;
        }

        try {
            $eventData = $data->optData('event');
            $event = null === $eventData ? null : Event::fromData($eventData);
        } catch (Throwable) {
            $event = null;
        }

        return match ($problem->slug()) {
            'total-changed' => new TotalChanged($problem, $quote, $stayQuote, $event),
            'quote-invalid' => new QuoteInvalid($problem, $quote, $stayQuote),
            'sold-out' => new SoldOut($problem, event: $event),
            'not-bookable' => new NotBookable($problem, event: $event),
            'idempotency-conflict' => new IdempotencyConflict($problem, $quote, $stayQuote),
            default => new Conflict($problem, $quote, $stayQuote, $event),
        };
    }

    /**
     * @return list<FieldError>
     */
    private static function fieldErrors(Data $data): array
    {
        $errors = [];

        try {
            foreach ($data->optList('errors') ?? [] as $error) {
                $errors[] = new FieldError($error->string('pointer'), $error->string('detail'), $error->optString('code'));
            }
        } catch (UnexpectedResponse) {
            // A malformed list still is a validation error.
        }

        return $errors;
    }

    private static function retryAfter(Result $result): ?int
    {
        $value = $result->header('Retry-After');

        return null !== $value && ctype_digit(trim($value)) ? (int) trim($value) : null;
    }
}
