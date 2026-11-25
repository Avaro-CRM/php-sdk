<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * The tenant rejected the link request for this external ID. The call
 * fails until the tenant lifts the rejection or links the user in Avaro.
 */
final class UserLinkRejected extends Conflict {}
