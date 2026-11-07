<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;

/**
 * A product in a list; price only with a customer token.
 */
final class ProductSummary
{
    /**
     * @param list<string> $categories IDs
     * @param list<string> $tags       Tags of the product, e.g. "startseite"
     */
    public function __construct(
        public readonly string $id,
        public readonly string $slug,
        public readonly string $sku,
        public readonly ?string $gtin,
        public readonly string $title,
        public readonly ?string $shortDescription,
        public readonly array $categories,
        public readonly ?Image $image,
        public readonly Availability $availability,
        public readonly ?CustomerPrice $price,
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
            $data->strings('categories'),
            null === $image ? null : Image::fromData($image),
            Availability::fromData($data->data('availability')),
            null === $price ? null : CustomerPrice::fromData($price),
            $data->strings('tags'),
        );
    }
}
