<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;

/**
 * A category with the ones below it.
 */
final class Category
{
    /**
     * @param list<Category> $children
     */
    public function __construct(
        public readonly string $id,
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?Image $image,
        public readonly array $children,
    ) {}

    public static function fromData(Data $data): self
    {
        $image = $data->optData('image');

        return new self(
            $data->string('id'),
            $data->string('slug'),
            $data->string('title'),
            $data->optString('description'),
            null === $image ? null : Image::fromData($image),
            array_map(self::fromData(...), $data->optList('children') ?? []),
        );
    }
}
