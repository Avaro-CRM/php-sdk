<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * The total of an order changed; quote is the new one.
 */
final class TotalChanged extends Conflict {}
