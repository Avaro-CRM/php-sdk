<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * An employee of the tenant already has the email, so Avaro recorded a
 * link request instead of creating a user. Once a tenant admin approves
 * it in Avaro, the same call returns the linked user; until then it
 * answers with this conflict again and only refreshes the request.
 */
final class UserLinkRequested extends Conflict {}
