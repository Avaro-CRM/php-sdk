<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;

/**
 * The settings of the events channel: the legal texts of a booking, the
 * details it must contain besides names and email, and the most
 * attendees per booking.
 */
final class Channel
{
    /**
     * @param list<LegalText>      $legal
     * @param list<RequiredDetail> $requiredDetails
     */
    public function __construct(
        public readonly string $currency,
        public readonly array $legal,
        public readonly array $requiredDetails,
        public readonly int $maxAttendees,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            $data->string('currency'),
            array_map(LegalText::fromData(...), $data->list('legal')),
            // A detail the SDK does not know cannot be filled in; Avaro then answers 422.
            array_values(array_filter(array_map(RequiredDetail::tryFrom(...), $data->strings('requiredDetails')))),
            $data->int('maxAttendees'),
        );
    }

    public function requires(RequiredDetail $detail): bool
    {
        return \in_array($detail, $this->requiredDetails, true);
    }
}
