<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * The event is not open for booking online (any more); event shows its
 * booking status now.
 */
final class NotBookable extends Conflict {}
