<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * Not enough free seats for the attendees; event is the event as it is now.
 */
final class SoldOut extends Conflict {}
