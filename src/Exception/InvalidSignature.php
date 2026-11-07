<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * A webhook without a valid signature, or too old.
 */
final class InvalidSignature extends AvaroException {}
