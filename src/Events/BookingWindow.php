<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;
use DateTimeImmutable;

/**
 * The booking status of an event and its window, each end null when open.
 */
final class BookingWindow
{
    public function __construct(
        public readonly BookingStatus $status,
        public readonly ?DateTimeImmutable $from,
        public readonly ?DateTimeImmutable $to,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            // A status the SDK does not know counts as not bookable online.
            BookingStatus::tryFrom($data->string('status')) ?? BookingStatus::Offline,
            $data->optDateTime('from'),
            $data->optDateTime('to'),
        );
    }
}
