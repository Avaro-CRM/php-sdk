<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * The idempotency key belongs to another body, or its first request
 * still runs.
 */
final class IdempotencyConflict extends Conflict {}
