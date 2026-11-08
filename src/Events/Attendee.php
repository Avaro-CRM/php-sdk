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
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?DateTimeInterface $dateOfBirth = null,
    ) {
        if ('' === trim($firstName) || '' === trim($lastName)) {
            throw new InvalidArgumentException('An attendee needs a first and a last name.');
        }
    }

    /**
     * @return array{firstName: string, lastName: string, dateOfBirth: ?string}
     */
    public function toArray(): array
    {
        return ['firstName' => $this->firstName, 'lastName' => $this->lastName, 'dateOfBirth' => $this->dateOfBirth?->format('Y-m-d')];
    }
}
