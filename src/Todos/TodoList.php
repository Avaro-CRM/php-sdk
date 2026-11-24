<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Model\Page;
use Generator;

/**
 * Todos of the tenant: list, counters, read, create, change and delete.
 */
final class TodoList extends Resource
{
    /** Assigned to the caller, or created by it without assignee. */
    public const ME = 'Me';
    /** Everyone else, including unassigned. */
    public const TEAM = 'Team';
    public const UNASSIGNED = 'Unassigned';
    public const ALL = 'All';

    /**
     * @param string                        $assignee         Me, Team, Unassigned, All or the Avaro ID of a user
     * @param ?string                       $status           Open (default) or Completed
     * @param ?string                       $dueTo            last due date; today lists due today and overdue
     * @param string|ExternalReference|AddressReference|null $address          Avaro ID or external reference of the address
     * @param ?string                       $sort             Due (default), -Updated or -Completed
     *
     * @return Page<Todo>
     */
    public function list(
        string $assignee = self::ME,
        ?string $status = null,
        ?string $dueFrom = null,
        ?string $dueTo = null,
        ?string $priority = null,
        ?string $type = null,
        string|ExternalReference|AddressReference|null $address = null,
        ?string $contact = null,
        ?string $sourceKey = null,
        ?string $sort = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): Page {
        $data = $this->transport->send('GET', '/todos/todos', [
            'assignee' => $assignee,
            'status' => $status,
            'dueFrom' => $dueFrom,
            'dueTo' => $dueTo,
            'priority' => $priority,
            'type' => $type,
            'address' => \is_string($address) ? $address : null,
            'addressReference' => $address instanceof ExternalReference ? $address->toString() : ($address instanceof AddressReference ? $address->reference()->toString() : null),
            'addressCustomerNumber' => $address instanceof AddressReference ? $address->customerNumber : null,
            'contact' => $contact,
            'sourceKey' => $sourceKey,
            'sort' => $sort,
            'limit' => $limit,
            'cursor' => $cursor,
        ])->data();

        return new Page(array_map(static fn($item): Todo => Todo::fromData($item), $data->list('data')), $data->optString('next'));
    }

    /**
     * Every matching todo, page by page.
     *
     * @return Generator<int, Todo>
     */
    public function each(
        string $assignee = self::ME,
        ?string $status = null,
        ?string $dueFrom = null,
        ?string $dueTo = null,
        string|ExternalReference|AddressReference|null $address = null,
        ?string $sourceKey = null,
        int $limit = 100,
    ): Generator {
        $cursor = null;

        do {
            $page = $this->list($assignee, $status, $dueFrom, $dueTo, address: $address, sourceKey: $sourceKey, limit: $limit, cursor: $cursor);

            foreach ($page->items as $todo) {
                yield $todo;
            }

            $cursor = $page->next;
        } while (null !== $cursor);
    }

    /**
     * @param ?string $today reference date (Y-m-d); today in the tenant's time zone when null
     */
    public function summary(string $assignee = self::ME, ?string $today = null): Summary
    {
        return Summary::fromData($this->transport->send('GET', '/todos/todos/summary', ['assignee' => $assignee, 'today' => $today])->data());
    }

    /**
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function get(string $id): Todo
    {
        $result = $this->transport->send('GET', '/todos/todos/' . self::segment($id));

        return Todo::fromData($result->data(), $result->header('ETag'));
    }

    /**
     * Creates a todo, or returns the open todo that holds its sourceKey
     * (created false). The same idempotency key returns the first answer.
     *
     * @throws \Avaro\Sdk\Exception\ValidationFailed
     * @throws \Avaro\Sdk\Exception\IdempotencyConflict
     */
    public function create(NewTodo $todo, ?string $idempotencyKey = null): CreatedTodo
    {
        $headers = null === $idempotencyKey ? [] : ['Idempotency-Key' => $idempotencyKey];
        $result = $this->transport->send('POST', '/todos/todos', json: $todo->toArray(), headers: $headers);

        return new CreatedTodo(Todo::fromData($result->data(), $result->header('ETag')), 201 === $result->status);
    }

    /**
     * Changes a todo. With ifMatch (the etag of get()) a change made in the
     * meantime is refused.
     *
     * @throws \Avaro\Sdk\Exception\PreconditionFailed when the todo changed since ifMatch
     * @throws \Avaro\Sdk\Exception\Forbidden          when the caller may not edit it
     * @throws \Avaro\Sdk\Exception\Conflict           when reopening and another open todo holds the sourceKey
     */
    public function update(string $id, Changes $changes, ?string $ifMatch = null): Todo
    {
        $headers = ['Content-Type' => 'application/merge-patch+json'] + (null === $ifMatch ? [] : ['If-Match' => $ifMatch]);
        $result = $this->transport->send('PATCH', '/todos/todos/' . self::segment($id), json: $changes->toArray(), headers: $headers);

        return Todo::fromData($result->data(), $result->header('ETag'));
    }

    /**
     * Moves the todo to the CRM trash of the tenant, where it can be restored.
     *
     * @throws \Avaro\Sdk\Exception\NotFound
     * @throws \Avaro\Sdk\Exception\Forbidden
     */
    public function delete(string $id): void
    {
        $this->transport->send('DELETE', '/todos/todos/' . self::segment($id));
    }
}
