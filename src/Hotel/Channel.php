<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;

/**
 * The settings of the hotel channel: the property it shows, the legal
 * texts of a request and the details a request must contain.
 */
final class Channel
{
    /**
     * @param list<LegalText>      $legal
     * @param list<RequiredDetail> $requiredDetails
     */
    public function __construct(
        public readonly Ref $property,
        public readonly string $currency,
        public readonly array $legal,
        public readonly array $requiredDetails = [],
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            Ref::fromData($data->data('property')),
            $data->string('currency'),
            array_map(LegalText::fromData(...), $data->list('legal')),
            // A detail the SDK does not know cannot be filled in; Avaro then answers 422.
            array_values(array_filter(array_map(RequiredDetail::tryFrom(...), $data->strings('requiredDetails')))),
        );
    }

    public function requires(RequiredDetail $detail): bool
    {
        return \in_array($detail, $this->requiredDetails, true);
    }
}
