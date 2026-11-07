<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;

/**
 * Why a stay cannot be requested: code such as Unavailable, MinNights,
 * MaxGuests, MaxInfants, MaxPets, NotBookable, ArrivalInPast or NoRate,
 * a text for people and the limit where there is one.
 */
final class QuoteProblem
{
    public function __construct(
        public readonly string $code,
        public readonly string $detail,
        public readonly ?int $limit,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('code'), $data->string('detail'), $data->optInt('limit'));
    }
}
