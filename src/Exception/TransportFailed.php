<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * The request did not reach Avaro or no response came.
 */
final class TransportFailed extends AvaroException {}
