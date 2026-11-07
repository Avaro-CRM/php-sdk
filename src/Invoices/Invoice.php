<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Invoices;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\Totals;
use DateTimeImmutable;

/**
 * An invoice; kind Invoice, CreditNote, CorrectionInvoice or
 * CreditNoteCancellation, status Open, Paid or Cancelled.
 */
final class Invoice
{
    /**
     * @param list<string> $orderIds
     */
    public function __construct(
        public readonly string $id,
        public readonly string $number,
        public readonly string $kind,
        public readonly string $status,
        public readonly string $currency,
        public readonly Totals $total,
        public readonly DateTimeImmutable $date,
        public readonly ?DateTimeImmutable $dueDate,
        public readonly array $orderIds,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->string('number'), $data->string('kind'), $data->string('status'), $data->string('currency'), Totals::fromData($data->data('total')), $data->dateTime('date'), $data->optDateTime('dueDate'), $data->strings('orderIds'));
    }
}
