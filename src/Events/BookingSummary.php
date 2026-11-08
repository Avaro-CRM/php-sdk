<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\Totals;
use DateTimeImmutable;

/**
 * A stored booking; Avaro sends the confirmation and, as set for the
 * tenant, the invoice.
 */
final class BookingSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $number,
        public readonly string $status,
        public readonly EventRef $event,
        public readonly int $attendees,
        public readonly string $currency,
        public readonly Totals $totals,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            $data->string('id'),
            $data->string('number'),
            $data->string('status'),
            EventRef::fromData($data->data('event')),
            $data->int('attendees'),
            $data->string('currency'),
            Totals::fromData($data->data('totals')),
            $data->dateTime('createdAt'),
        );
    }
}
