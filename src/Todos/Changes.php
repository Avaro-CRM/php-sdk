<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use DateTimeInterface;
use DateTimeZone;
use LogicException;

/**
 * Changes of a todo as JSON Merge Patch: only fields that were set are
 * sent, null clears a field.
 *
 *     $changes = Changes::new()->complete();
 *     $changes = Changes::new()->due('2026-11-30', '10:00')->assignee($userId);
 */
final class Changes
{
    /**
     * @param array<string, mixed> $fields
     */
    private function __construct(private readonly array $fields = []) {}

    public static function new(): self
    {
        return new self();
    }

    public function title(string $title): self
    {
        return $this->with('title', $title);
    }

    public function note(?string $note): self
    {
        return $this->with('note', $note);
    }

    public function type(string $type): self
    {
        return $this->with('type', $type);
    }

    public function priority(string $priority): self
    {
        return $this->with('priority', $priority);
    }

    public function complete(): self
    {
        return $this->with('status', Todo::COMPLETED);
    }

    public function reopen(): self
    {
        return $this->with('status', Todo::OPEN);
    }

    /**
     * Moves the todo; an existing reminder moves by the same offset unless
     * reminder() is set too. due(null) clears date and time.
     */
    public function due(?string $date, ?string $time = null): self
    {
        return null === $date ? $this->with('dueDate', null) : $this->with('dueDate', $date)->with('dueTime', $time);
    }

    public function reminder(?DateTimeInterface $at): self
    {
        return $this->with('reminderAt', self::time($at));
    }

    /** The Avaro ID of the new assignee; null leaves the todo without one. */
    public function assignee(?string $userId): self
    {
        return $this->with('assignee', $userId);
    }

    public function address(string|ExternalReference|AddressReference|null $address): self
    {
        return $this->with('address', self::selector($address));
    }

    public function contact(string|ExternalReference|null $contact): self
    {
        return $this->with('contact', self::selector($contact));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [] === $this->fields ? throw new LogicException('Set at least one change.') : $this->fields;
    }

    /**
     * @internal
     *
     * @return array<string, mixed>|null
     */
    public static function selector(string|ExternalReference|AddressReference|null $value): ?array
    {
        return match (true) {
            null === $value => null,
            $value instanceof ExternalReference => ['reference' => $value->toArray()],
            $value instanceof AddressReference => ['reference' => $value->reference()->toArray()] + (null === $value->customerNumber ? [] : ['customerNumber' => $value->customerNumber]),
            default => ['id' => $value],
        };
    }

    /** @internal */
    public static function time(?DateTimeInterface $time): ?string
    {
        return $time === null ? null : \DateTimeImmutable::createFromInterface($time)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    private function with(string $field, mixed $value): self
    {
        return new self([$field => $value] + $this->fields);
    }
}
