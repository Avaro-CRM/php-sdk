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
 * A calculated stay; valid when it has no problem and can be requested.
 * With priceOnRequest a night has no seasonal price: no lines, totals 0,
 * the tenant names the price. Send totals->gross with the request.
 */
final class Quote
{
    /**
     * @param list<QuoteLine>    $lines
     * @param list<QuoteProblem> $problems
     */
    public function __construct(
        public readonly Ref $unit,
        public readonly DateTimeImmutable $arrival,
        public readonly DateTimeImmutable $departure,
        public readonly int $nights,
        public readonly Guests $guests,
        public readonly string $currency,
        public readonly array $lines,
        public readonly Totals $totals,
        public readonly bool $valid,
        public readonly array $problems,
        public readonly bool $priceOnRequest = false,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            Ref::fromData($data->data('unit')),
            $data->dateTime('arrival'),
            $data->dateTime('departure'),
            $data->int('nights'),
            Guests::fromData($data->data('guests')),
            $data->string('currency'),
            array_map(QuoteLine::fromData(...), $data->list('lines')),
            Totals::fromData($data->data('totals')),
            $data->bool('valid'),
            array_map(QuoteProblem::fromData(...), $data->list('problems')),
            $data->bool('priceOnRequest', false),
        );
    }

    public function hasProblem(string $code): bool
    {
        foreach ($this->problems as $problem) {
            if ($problem->code === $code) {
                return true;
            }
        }

        return false;
    }
}
