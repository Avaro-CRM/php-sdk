<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Shop;

use Avaro\Sdk\Model\Data;

/**
 * An image; copy the file from url, again when version changes.
 */
final class Image
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly ?string $alt,
        public readonly string $version,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('id'), $data->string('url'), $data->optInt('width'), $data->optInt('height'), $data->optString('alt'), $data->string('version'));
    }
}
