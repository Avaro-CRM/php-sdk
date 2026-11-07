<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use DateTimeImmutable;

/**
 * A bearer token of the client (avt_…).
 */
final class ClientToken
{
    public function __construct(
        #[\SensitiveParameter] public readonly string $accessToken,
        public readonly DateTimeImmutable $expiresAt,
        public readonly string $scope,
    ) {}
}
