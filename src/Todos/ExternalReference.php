<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use Avaro\Sdk\Model\Data;

/**
 * An ID of an object in another system, e.g. a platform and the public ID
 * of a company there.
 */
final class ExternalReference
{
    public function __construct(
        public readonly string $system,
        public readonly string $externalId,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('system'), $data->string('externalId'));
    }

    /**
     * @param list<Data> $items
     *
     * @return list<self>
     */
    public static function list(array $items): array
    {
        return array_map(self::fromData(...), $items);
    }

    /** As in the filter addressReference: <system>:<externalId>. */
    public function toString(): string
    {
        return $this->system . ':' . $this->externalId;
    }

    /**
     * @return array{system: string, externalId: string}
     */
    public function toArray(): array
    {
        return ['system' => $this->system, 'externalId' => $this->externalId];
    }
}
