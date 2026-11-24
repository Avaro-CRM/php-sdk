<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Crm;

use Avaro\Sdk\Todos\AddressReference;
use Avaro\Sdk\Todos\Changes;
use Avaro\Sdk\Todos\ExternalReference;
use DateTimeInterface;

/**
 * An activity to record: a note by default, else Activities::PHONE_CALL,
 * MEETING or EMAIL. address and contact are an Avaro ID or an external
 * reference, the address also an AddressReference with customer number.
 * occurredAt is now when null and never in the future. sourceKey names the
 * event of the caller: the same key records nothing a second time.
 */
final class NewActivity
{
    public function __construct(
        public readonly string|ExternalReference|AddressReference $address,
        public readonly string $text,
        public readonly ?string $type = null,
        public readonly ?string $title = null,
        public readonly string|ExternalReference|null $contact = null,
        public readonly ?DateTimeInterface $occurredAt = null,
        public readonly ?string $direction = null,
        public readonly ?int $callDurationSeconds = null,
        public readonly ?string $callStatus = null,
        public readonly ?string $callOutcome = null,
        public readonly ?string $sourceKey = null,
    ) {}

    public static function note(string|ExternalReference|AddressReference $address, string $text, ?string $sourceKey = null): self
    {
        return new self($address, $text, sourceKey: $sourceKey);
    }

    /**
     * @param ?string $direction Incoming or Outgoing
     * @param ?string $status    a Call constant
     */
    public static function phoneCall(
        string|ExternalReference|AddressReference $address,
        string $text,
        ?string $direction = null,
        ?int $durationSeconds = null,
        ?string $status = null,
        ?string $outcome = null,
        ?DateTimeInterface $occurredAt = null,
        string|ExternalReference|null $contact = null,
        ?string $sourceKey = null,
    ): self {
        return new self($address, $text, Activities::PHONE_CALL, null, $contact, $occurredAt, $direction, $durationSeconds, $status, $outcome, $sourceKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $call = array_filter(['durationSeconds' => $this->callDurationSeconds, 'status' => $this->callStatus, 'outcome' => $this->callOutcome], static fn(mixed $value): bool => null !== $value);

        return array_filter([
            'type' => $this->type,
            'address' => Changes::selector($this->address),
            'contact' => Changes::selector($this->contact),
            'title' => $this->title,
            'text' => $this->text,
            'occurredAt' => Changes::time($this->occurredAt),
            'direction' => $this->direction,
            'call' => [] === $call ? null : $call,
            'sourceKey' => $this->sourceKey,
        ], static fn(mixed $value): bool => null !== $value);
    }
}
