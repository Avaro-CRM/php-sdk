<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;
use DateTimeImmutable;

/**
 * One product with its images and description (HTML; sanitise it).
 */
final class Product
{
    /**
     * @param list<string> $categories IDs
     * @param list<string> $tags       Tags of the product, e.g. "startseite"
     * @param list<Image>  $images
     */
    public function __construct(
        public readonly string $id,
        public readonly string $slug,
        public readonly string $sku,
        public readonly ?string $gtin,
        public readonly string $title,
        public readonly ?string $shortDescription,
        public readonly ?string $description,
        public readonly array $categories,
        public readonly ?Image $image,
        public readonly array $images,
        public readonly Availability $availability,
        public readonly ?CustomerPrice $price,
        public readonly string $unit,
        public readonly int|float $minQuantity,
        public readonly int|float $quantityStep,
        public readonly ?DateTimeImmutable $updatedAt,
        public readonly array $tags = [],
    ) {}

    public static function fromData(Data $data): self
    {
        $image = $data->optData('image');
        $price = $data->optData('price');

        return new self(
            $data->string('id'),
            $data->string('slug'),
            $data->string('sku'),
            $data->optString('gtin'),
            $data->string('title'),
            $data->optString('shortDescription'),
            $data->optString('description'),
            $data->strings('categories'),
            null === $image ? null : Image::fromData($image),
            array_map(Image::fromData(...), $data->list('images')),
            Availability::fromData($data->data('availability')),
            null === $price ? null : CustomerPrice::fromData($price),
            $data->string('unit'),
            $data->number('minQuantity'),
            $data->number('quantityStep'),
            $data->optDateTime('updatedAt'),
            $data->strings('tags'),
        );
    }
}
