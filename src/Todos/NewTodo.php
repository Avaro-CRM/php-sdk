<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use DateTimeInterface;

/**
 * A todo to create. address and contact are an Avaro ID or an external
 * reference, the address also an AddressReference with customer number; assignee is the Avaro ID of a user, the caller when null.
 * sourceKey names the event of the caller the todo is for, e.g.
 * SubscriptionTerminated:<id>: while such a todo is open, no second one is
 * created.
 */
final class NewTodo
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $note = null,
        public readonly ?string $type = null,
        public readonly ?string $priority = null,
        public readonly ?string $dueDate = null,
        public readonly ?string $dueTime = null,
        public readonly ?DateTimeInterface $reminderAt = null,
        public readonly ?string $assignee = null,
        public readonly string|ExternalReference|AddressReference|null $address = null,
        public readonly string|ExternalReference|null $contact = null,
        public readonly ?string $sourceKey = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'note' => $this->note,
            'type' => $this->type,
            'priority' => $this->priority,
            'dueDate' => $this->dueDate,
            'dueTime' => $this->dueTime,
            'reminderAt' => Changes::time($this->reminderAt),
            'assignee' => $this->assignee,
            'address' => Changes::selector($this->address),
            'contact' => Changes::selector($this->contact),
            'sourceKey' => $this->sourceKey,
        ], static fn(mixed $value): bool => null !== $value);
    }
}
