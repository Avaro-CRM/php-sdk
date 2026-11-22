<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * A person who attends; may be the customer.
 */
final class Attendee
{
    /** @param array<int|string, string|bool|int|float|null> $additionalFields Values keyed by participant field ID. */
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?DateTimeInterface $dateOfBirth = null,
        public readonly array $additionalFields = [],
    ) {
        if ('' === trim($firstName) || '' === trim($lastName)) {
            throw new InvalidArgumentException('An attendee needs a first and a last name.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['firstName' => $this->firstName, 'lastName' => $this->lastName, 'dateOfBirth' => $this->dateOfBirth?->format('Y-m-d')];
        if ($this->additionalFields !== []) $data['additionalFields'] = (object) $this->additionalFields;
        return $data;
    }
}
