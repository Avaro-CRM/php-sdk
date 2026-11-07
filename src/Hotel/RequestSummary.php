<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\Totals;
use DateTimeImmutable;

/**
 * A stored booking request, status Requested until the tenant decides.
 */
final class RequestSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $number,
        public readonly string $status,
        public readonly Ref $unit,
        public readonly DateTimeImmutable $arrival,
        public readonly DateTimeImmutable $departure,
        public readonly Guests $guests,
        public readonly string $currency,
        public readonly Totals $totals,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            $data->string('id'),
            $data->string('number'),
            $data->string('status'),
            Ref::fromData($data->data('unit')),
            $data->dateTime('arrival'),
            $data->dateTime('departure'),
            Guests::fromData($data->data('guests')),
            $data->string('currency'),
            Totals::fromData($data->data('totals')),
            $data->dateTime('createdAt'),
        );
    }
}
