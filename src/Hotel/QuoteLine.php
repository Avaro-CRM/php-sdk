<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Model\Totals;
use Avaro\Sdk\Model\UnitPrice;

/**
 * A line of a quote, e.g. 3 nights in the main season.
 */
final class QuoteLine
{
    /**
     * @param list<string> $includes what a fee includes
     */
    public function __construct(
        public readonly LineType $type,
        public readonly string $title,
        public readonly ?Season $season,
        public readonly int $quantity,
        public readonly UnitPrice $unitPrice,
        public readonly Totals $totals,
        public readonly string $taxRate,
        public readonly array $includes,
    ) {}

    public static function fromData(Data $data): self
    {
        $season = $data->optData('season');

        return new self(
            // A type the SDK does not know counts as a fee.
            LineType::tryFrom($data->string('type')) ?? LineType::Fee,
            $data->string('title'),
            null === $season ? null : Season::fromData($season),
            $data->int('quantity'),
            UnitPrice::fromData($data->data('unitPrice')),
            Totals::fromData($data->data('totals')),
            $data->string('taxRate'),
            $data->strings('includes'),
        );
    }
}
