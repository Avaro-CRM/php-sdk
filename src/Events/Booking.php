<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use InvalidArgumentException;

/**
 * A booking of one event for one or more attendees: the customer, the
 * values of the event's fields by key, the legal texts agreed to and the
 * gross total the customer saw.
 */
final class Booking
{
    /**
     * @param list<Attendee>             $attendees
     * @param array<string, bool|string> $fields
     * @param list<Consent>              $consents
     */
    public function __construct(
        public readonly string $event,
        public readonly Customer $customer,
        public readonly array $attendees,
        public readonly int $expectedGross,
        public readonly array $fields = [],
        public readonly array $consents = [],
        public readonly ?string $message = null,
    ) {
        if ([] === $attendees || \count($attendees) > 50) {
            throw new InvalidArgumentException('A booking needs 1 to 50 attendees.');
        }
    }

    /**
     * A booking of this event with the total Avaro will check.
     *
     * @param list<Attendee>             $attendees
     * @param array<string, bool|string> $fields
     * @param list<Consent>              $consents
     */
    public static function of(Event $event, Customer $customer, array $attendees, array $fields = [], array $consents = [], ?string $message = null): self
    {
        return new self($event->id, $customer, $attendees, $event->totalGross(\count($attendees)), $fields, $consents, $message);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'customer' => $this->customer->toArray(),
            'attendees' => array_map(static fn(Attendee $attendee): array => $attendee->toArray(), $this->attendees),
            // An empty object, never a list.
            'fields' => (object) $this->fields,
            'message' => $this->message,
            'consents' => array_map(static fn(Consent $consent): array => ['key' => $consent->key, 'version' => $consent->version], $this->consents),
            'expectedTotal' => ['gross' => $this->expectedGross],
        ];
    }
}
