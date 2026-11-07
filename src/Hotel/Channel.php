<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;

/**
 * The settings of the hotel channel: the property it shows and the legal
 * texts of a request.
 */
final class Channel
{
    /**
     * @param list<LegalText> $legal
     */
    public function __construct(
        public readonly Ref $property,
        public readonly string $currency,
        public readonly array $legal,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(Ref::fromData($data->data('property')), $data->string('currency'), array_map(LegalText::fromData(...), $data->list('legal')));
    }
}
