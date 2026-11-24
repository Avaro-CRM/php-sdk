<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use Avaro\Sdk\Model\Data;
use DateTimeImmutable;

/**
 * A todo. dueDate and dueTime (HH:MM) are wall-clock values in timeZone;
 * the other times are UTC. etag is set when the todo was read alone and is
 * the If-Match of a later update().
 */
final class Todo
{
    public const OPEN = 'Open';
    public const COMPLETED = 'Completed';

    public const TODO = 'Todo';
    public const CALL = 'Call';
    public const EMAIL = 'Email';
    public const MEETING = 'Meeting';

    public const NORMAL = 'Normal';
    public const HIGH = 'High';

    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $note,
        public readonly string $type,
        public readonly string $priority,
        public readonly string $status,
        public readonly ?string $dueDate,
        public readonly ?string $dueTime,
        public readonly string $timeZone,
        public readonly ?DateTimeImmutable $reminderAt,
        public readonly ?DateTimeImmutable $completedAt,
        public readonly ?UserRef $assignee,
        public readonly ?UserRef $createdBy,
        public readonly ?AddressRef $address,
        public readonly ?ContactRef $contact,
        public readonly ?ExternalReference $source,
        public readonly ?string $sourceKey,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
        public readonly bool $canEdit,
        public readonly bool $canDelete,
        public readonly ?string $etag = null,
    ) {}

    public static function fromData(Data $data, ?string $etag = null): self
    {
        $assignee = $data->optData('assignee');
        $creator = $data->optData('createdBy');
        $address = $data->optData('address');
        $contact = $data->optData('contact');
        $source = $data->optData('source');
        $permissions = $data->data('permissions');

        return new self(
            $data->string('id'),
            $data->string('title'),
            $data->optString('note'),
            $data->string('type'),
            $data->string('priority'),
            $data->string('status'),
            $data->optString('dueDate'),
            $data->optString('dueTime'),
            $data->string('timeZone'),
            $data->optDateTime('reminderAt'),
            $data->optDateTime('completedAt'),
            null === $assignee ? null : UserRef::fromData($assignee),
            null === $creator ? null : UserRef::fromData($creator),
            null === $address ? null : AddressRef::fromData($address),
            null === $contact ? null : ContactRef::fromData($contact),
            null === $source ? null : ExternalReference::fromData($source),
            $data->optString('sourceKey'),
            $data->dateTime('createdAt'),
            $data->dateTime('updatedAt'),
            $permissions->bool('edit'),
            $permissions->bool('delete'),
            $etag,
        );
    }

    public function isOpen(): bool
    {
        return self::OPEN === $this->status;
    }
}
