<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Crm;

use Avaro\Sdk\Model\Data;

/**
 * Details of a phone call. status is Completed, NoAnswer, Busy, Failed or
 * Canceled, for imported calls also Queued, Ringing or Connecting.
 */
final class Call
{
    public const COMPLETED = 'Completed';
    public const NO_ANSWER = 'NoAnswer';
    public const BUSY = 'Busy';
    public const FAILED = 'Failed';
    public const CANCELED = 'Canceled';

    public function __construct(
        public readonly ?int $durationSeconds,
        public readonly ?string $status,
        public readonly ?string $outcome,
        public readonly ?string $fromNumber,
        public readonly ?string $toNumber,
    ) {}

    public static function fromData(Data $data): self
    {
        return new self($data->optInt('durationSeconds'), $data->optString('status'), $data->optString('outcome'), $data->optString('fromNumber'), $data->optString('toNumber'));
    }
}
