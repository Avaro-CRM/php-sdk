<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * The total of an order, request or booking changed; quote (shop),
 * stayQuote (hotel) or event (events) shows the new price.
 */
final class TotalChanged extends Conflict {}
