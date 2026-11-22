<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;

/**
 * A question of a booking, e.g. "Kosten werden über die Firma bzw. BG
 * abgerechnet". A required Checkbox has to be ticked. type is null for a
 * type the SDK does not know: an event that requires such a field cannot
 * be booked with it.
 */
final class Field
{
    /**
     * @param list<FieldOption> $options for Choice; empty otherwise
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly ?FieldType $type,
        public readonly bool $required,
        public readonly array $options,
        public readonly ?string $placeholder = null,
        public readonly ?string $hint = null,
        public readonly ?string $defaultValue = null,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self(
            $data->string('key'),
            $data->string('title'),
            FieldType::tryFrom($data->string('type')),
            $data->bool('required', false),
            array_map(FieldOption::fromData(...), $data->optList('options') ?? []),
            $data->optString('placeholder'),
            $data->optString('hint'),
            $data->optString('defaultValue'),
        );
    }
}
