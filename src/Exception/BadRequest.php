<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * 400, e.g. an OAuth error of the token endpoint.
 */
final class BadRequest extends ApiException {}
