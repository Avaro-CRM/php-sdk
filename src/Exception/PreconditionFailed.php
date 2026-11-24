<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * 412: the object was changed after the version given as If-Match; read
 * it again and repeat the change.
 */
final class PreconditionFailed extends ApiException {}
