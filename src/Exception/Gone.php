<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * 410: e.g. a position of the catalog sync that is too old
 * (since-expired), or a used reset token.
 */
final class Gone extends ApiException {}
