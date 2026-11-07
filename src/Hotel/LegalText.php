<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Hotel;

use Avaro\Sdk\Model\Data;

/**
 * A legal text a guest agrees to when sending a request, in a version.
 */
final class LegalText
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $version,
        public readonly string $url,
        public readonly bool $required,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('key'), $data->string('title'), $data->string('version'), $data->string('url'), $data->bool('required', false));
    }
}
