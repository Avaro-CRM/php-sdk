<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Http\Resource;

/**
 * Calculates a stay, writing nothing; problems say why it cannot be
 * requested, e.g. MinNights or Unavailable.
 */
final class Quotes extends Resource
{
    /**
     * @throws \Avaro\Sdk\Exception\NotFound         for an unknown unit
     * @throws \Avaro\Sdk\Exception\ValidationFailed e.g. dates in the wrong order
     */
    public function create(Stay $stay): Quote
    {
        if (null === $stay->unit) {
            throw new \InvalidArgumentException('A quote needs the unit of the stay.');
        }

        return Quote::fromData($this->transport->send('POST', '/hotel/quotes', json: $stay->toArray())->data());
    }
}
