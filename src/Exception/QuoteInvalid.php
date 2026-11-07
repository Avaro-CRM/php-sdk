<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * The order cannot be placed; the problems of the lines of quote say why.
 */
final class QuoteInvalid extends Conflict {}
