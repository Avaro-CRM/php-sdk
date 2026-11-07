<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use InvalidArgumentException;

/**
 * A booking request: the stay, the guest, the legal texts agreed to and
 * the gross total of the quote the guest saw.
 */
final class BookingRequest
{
    /**
     * @param list<Consent> $consents
     */
    public function __construct(
        public readonly Stay $stay,
        public readonly Guest $guest,
        public readonly int $expectedGross,
        public readonly array $consents = [],
        public readonly ?string $message = null,
    ) {
        if (null === $stay->unit) {
            throw new InvalidArgumentException('A request needs the unit of the stay.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->stay->toArray(),
            'guest' => $this->guest->toArray(),
            'message' => $this->message,
            'consents' => array_map(static fn(Consent $consent): array => ['key' => $consent->key, 'version' => $consent->version], $this->consents),
            'expectedTotal' => ['gross' => $this->expectedGross],
        ];
    }
}
