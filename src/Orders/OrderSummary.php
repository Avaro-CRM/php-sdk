<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Orders;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\Totals;
use DateTimeImmutable;

/**
 * An order in a list; status Created, Open, Completed, Closed or
 * Cancelled.
 */
final class OrderSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $number,
        public readonly string $status,
        public readonly string $currency,
        public readonly Totals $total,
        public readonly ?string $reference,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->string('number'), $data->string('status'), $data->string('currency'), Totals::fromData($data->data('total')), $data->optString('reference'), $data->dateTime('createdAt'));
    }
}
