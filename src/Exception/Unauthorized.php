<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * 401: the client or the customer token is not valid (any more), or
 * email and password were wrong.
 */
final class Unauthorized extends ApiException {}
