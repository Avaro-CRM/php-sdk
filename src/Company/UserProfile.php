<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

/**
 * Email (the login), first and last name of a user to provision.
 */
final class UserProfile
{
    public function __construct(
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}

    /**
     * @return array{email: string, firstName: string, lastName: string}
     */
    public function toArray(): array
    {
        return ['email' => $this->email, 'firstName' => $this->firstName, 'lastName' => $this->lastName];
    }
}
