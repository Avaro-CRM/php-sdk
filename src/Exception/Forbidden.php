<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * 403: a scope is missing, the tenant is inactive or the client needs a
 * user.
 */
final class Forbidden extends ApiException {}
