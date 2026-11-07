<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use DateTimeImmutable;

/**
 * A sign-in: the customer token (avc_…) and the customer.
 */
final class CustomerLogin
{
    public function __construct(
        #[\SensitiveParameter] public readonly string $token,
        public readonly DateTimeImmutable $expiresAt,
        public readonly Customer $customer,
    ) {}
}
