<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Crm;

/**
 * The answer of Activities::create(): created false when the client had
 * recorded an activity with the same sourceKey before.
 */
final class CreatedActivity
{
    public function __construct(
        public readonly Activity $activity,
        public readonly bool $created,
    ) {}
}
