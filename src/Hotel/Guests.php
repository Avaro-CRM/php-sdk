<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use InvalidArgumentException;

/**
 * Who comes: adults and children count as guests, infants sleep in a cot.
 */
final class Guests
{
    public function __construct(
        public readonly int $adults,
        public readonly int $children = 0,
        public readonly int $infants = 0,
        public readonly int $pets = 0,
    ) {
        if ($adults < 1 || $children < 0 || $infants < 0 || $pets < 0) {
            throw new InvalidArgumentException('At least one adult, and no negative numbers.');
        }
    }

    public static function fromData(Data $data): self
    {
        return new self($data->int('adults'), $data->optInt('children') ?? 0, $data->optInt('infants') ?? 0, $data->optInt('pets') ?? 0);
    }

    /**
     * @return array{adults: int, children: int, infants: int, pets: int}
     */
    public function toArray(): array
    {
        return ['adults' => $this->adults, 'children' => $this->children, 'infants' => $this->infants, 'pets' => $this->pets];
    }
}
