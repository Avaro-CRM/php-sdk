<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Crm;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Todos\AddressRef;
use Avaro\Sdk\Todos\ContactRef;
use Avaro\Sdk\Todos\UserRef;
use DateTimeImmutable;

/**
 * An activity of an address. type is Note, PhoneCall, Meeting (visit),
 * Email or CalendarEntry, more may follow. origin is Manual (in Avaro), Api,
 * HubSpot, Email or CalendarEvent. text is plain text; escape it on display.
 */
final class Activity
{
    /**
     * @param list<Participant> $participants without Bcc
     * @param list<AddressRef>  $addresses
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly DateTimeImmutable $occurredAt,
        public readonly ?string $title,
        public readonly ?string $text,
        public readonly bool $textTruncated,
        public readonly ?string $direction,
        public readonly ?UserRef $author,
        public readonly ?ContactRef $contact,
        public readonly array $participants,
        public readonly ?Call $call,
        public readonly array $addresses,
        public readonly string $origin,
        public readonly ?string $sourceKey,
        public readonly bool $canEdit,
        public readonly bool $canDelete,
    ) {}

    public static function fromData(Data $data): self
    {
        $author = $data->optData('author');
        $contact = $data->optData('contact');
        $call = $data->optData('call');
        $permissions = $data->data('permissions');

        return new self(
            $data->string('id'),
            $data->string('type'),
            $data->dateTime('occurredAt'),
            $data->optString('title'),
            $data->optString('text'),
            $data->bool('textTruncated', false),
            $data->optString('direction'),
            null === $author ? null : UserRef::fromData($author),
            null === $contact ? null : ContactRef::fromData($contact),
            array_map(static fn($item): Participant => Participant::fromData($item), $data->list('participants')),
            null === $call ? null : Call::fromData($call),
            array_map(static fn($item): AddressRef => AddressRef::fromData($item), $data->list('addresses')),
            $data->string('origin'),
            $data->optString('sourceKey'),
            $permissions->bool('edit', false),
            $permissions->bool('delete', false),
        );
    }
}
