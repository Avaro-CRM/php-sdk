<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * 5xx; quote the diagnostic ID of the problem to support.
 */
final class ServerError extends ApiException {}
