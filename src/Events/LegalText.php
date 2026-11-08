<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Events;

use Avaro\Sdk\Model\Data;

/**
 * A legal text a customer agrees to when booking, in a version: with a
 * url, or shown with the consent only as text (e.g. terms of
 * cancellation).
 */
final class LegalText
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $version,
        public readonly ?string $url,
        public readonly ?string $text,
        public readonly bool $required,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->string('key'), $data->string('title'), $data->string('version'), $data->optString('url'), $data->optString('text'), $data->bool('required', false));
    }
}
