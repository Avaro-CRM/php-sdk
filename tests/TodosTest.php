<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Auth\ApiKey;
use Avaro\Sdk\Auth\ClientCredentials;
use Avaro\Sdk\Exception\BadRequest;
use Avaro\Sdk\Exception\Conflict;
use Avaro\Sdk\Exception\PreconditionFailed;
use Avaro\Sdk\Todos\AddressReference;
use Avaro\Sdk\Todos\Changes;
use Avaro\Sdk\Todos\ExternalReference;
use Avaro\Sdk\Todos\NewTodo;
use Avaro\Sdk\Todos\Todo;
use Avaro\Sdk\Todos\TodoList;
use Avaro\Sdk\Tests\Support\Fixtures;
use LogicException;

final class TodosTest extends TestCase
{
    private const USER_TOKEN = 'avu_0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    public function testAsUserExchangesTheTokenOnceAndSendsItAsBearer(): void
    {
        $this->http
            ->answer(200, ['access_token' => self::USER_TOKEN, 'issued_token_type' => 'urn:ietf:params:oauth:token-type:access_token', 'token_type' => 'Bearer', 'expires_in' => 3600, 'scope' => 'todos.todos:read todos.todos:write'])
            ->answer(200, Fixtures::load('todo-summary'))
            ->answer(200, ['data' => [Fixtures::load('todo')], 'next' => null]);
        $avaro = $this->avaro(new ClientCredentials('0123456789abcdef0123', 'secret'))->asUser('01HXA7Q2Z6V7C1M4K9P0R3S5T8');

        $summary = $avaro->todos()->todos()->summary();
        $avaro->todos()->todos()->list();

        self::assertCount(3, $this->http->requests, 'The token is exchanged once.');
        $exchange = $this->http->requests[0];
        self::assertSame('https://app.avaro.test/rest/v3/auth/token', (string) $exchange->getUri());
        self::assertSame('Basic ' . base64_encode('0123456789abcdef0123:secret'), $exchange->getHeaderLine('Authorization'));
        parse_str((string) $exchange->getBody(), $form);
        self::assertSame([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
            'subject_token' => '01HXA7Q2Z6V7C1M4K9P0R3S5T8',
            'subject_token_type' => 'urn:avaro:params:oauth:token-type:external-user-id',
        ], $form);
        self::assertSame('Bearer ' . self::USER_TOKEN, $this->http->last()->getHeaderLine('Authorization'));
        self::assertSame([881, 7, 848, 46], [$summary->open, $summary->dueToday, $summary->overdue, $summary->highPriority]);
    }

    public function testAsUserNeedsClientCredentials(): void
    {
        $this->expectException(LogicException::class);
        $this->avaro(new ApiKey('avk_0123456789abcdef0123_secret'))->asUser('01HX');
    }

    public function testAnUnknownUserIsAnInvalidGrant(): void
    {
        $this->http->answer(400, ['error' => 'invalid_grant', 'error_description' => 'There is no active user with this external ID.']);

        try {
            $this->avaro(new ClientCredentials('0123456789abcdef0123', 'secret'))->asUser('unknown')->todos()->todos()->summary();
            self::fail('No exception.');
        } catch (BadRequest $error) {
            self::assertSame('oauth:invalid_grant', $error->problem->type);
        }
    }

    public function testListSendsTheFiltersAndReadsTheTodos(): void
    {
        $this->http->answer(200, ['data' => [Fixtures::load('todo')], 'next' => 'eyJpZCI6MX0']);

        $page = $this->avaro()->todos()->todos()->list(assignee: TodoList::ALL, dueTo: '2026-11-24', address: new ExternalReference('ExamplePlatform', '01HXB0COMPANY0000000000000'), sourceKey: 'SubscriptionTerminated:1', limit: 100);

        parse_str($this->http->last()->getUri()->getQuery(), $query);
        self::assertSame(['assignee' => 'All', 'dueTo' => '2026-11-24', 'addressReference' => 'ExamplePlatform:01HXB0COMPANY0000000000000', 'sourceKey' => 'SubscriptionTerminated:1', 'limit' => '100'], $query);
        self::assertSame('eyJpZCI6MX0', $page->next);

        $todo = $page->items[0];
        self::assertSame(['Call', 'High', 'Open', '2026-11-25', '10:30'], [$todo->type, $todo->priority, $todo->status, $todo->dueDate, $todo->dueTime]);
        self::assertTrue($todo->isOpen());
        self::assertSame('01HXA7Q2Z6V7C1M4K9P0R3S5T8', $todo->assignee?->referenceOf('ExamplePlatform'));
        self::assertNull($todo->createdBy?->referenceOf('ExamplePlatform'));
        self::assertSame('10042', $todo->address?->customerNumber);
        self::assertSame('2026-11-25T09:00:00+00:00', $todo->reminderAt?->format(\DATE_ATOM));
        self::assertTrue($todo->canEdit);
        self::assertNull($todo->etag, 'Todos of a list have no ETag.');
    }

    public function testEachFollowsTheCursor(): void
    {
        $this->http
            ->answer(200, ['data' => [Fixtures::load('todo')], 'next' => 'c2'])
            ->answer(200, ['data' => [Fixtures::load('todo')], 'next' => null]);

        self::assertCount(2, iterator_to_array($this->avaro()->todos()->todos()->each(assignee: TodoList::TEAM), false));
        parse_str($this->http->last()->getUri()->getQuery(), $query);
        self::assertSame('c2', $query['cursor']);
    }

    public function testCreateSendsTheTodoAndTellsWhetherItIsNew(): void
    {
        $this->http
            ->answer(201, Fixtures::load('todo'), ['ETag' => '"abc"', 'Location' => '/rest/v3/todos/todos/01JD2Q6R8Y4V8N3K5T7W9X1Z2A'])
            ->answer(200, Fixtures::load('todo'), ['ETag' => '"abc"']);
        $todos = $this->avaro()->todos()->todos();
        $new = new NewTodo('Rückruf zur Kündigung', type: Todo::CALL, priority: Todo::HIGH, dueDate: '2026-11-25', dueTime: '10:30',
            reminderAt: new \DateTimeImmutable('2026-11-25 10:00:00', new \DateTimeZone('Europe/Berlin')),
            address: new ExternalReference('ExamplePlatform', '01HXB0COMPANY0000000000000'), sourceKey: 'SubscriptionTerminated:01HXC0SUBSCRIPTION000000000');

        $created = $todos->create($new, 'subscription-terminated-1');

        self::assertTrue($created->created);
        self::assertSame('"abc"', $created->todo->etag);
        self::assertSame('subscription-terminated-1', $this->http->last()->getHeaderLine('Idempotency-Key'));
        self::assertSame('application/json', $this->http->last()->getHeaderLine('Content-Type'));
        self::assertSame([
            'title' => 'Rückruf zur Kündigung',
            'type' => 'Call',
            'priority' => 'High',
            'dueDate' => '2026-11-25',
            'dueTime' => '10:30',
            'reminderAt' => '2026-11-25T09:00:00Z',
            'address' => ['reference' => ['system' => 'ExamplePlatform', 'externalId' => '01HXB0COMPANY0000000000000']],
            'sourceKey' => 'SubscriptionTerminated:01HXC0SUBSCRIPTION000000000',
        ], $this->http->lastJson());

        self::assertFalse($todos->create($new)->created, 'An open todo with the sourceKey comes back with 200.');
    }

    public function testGetUpdateAndDelete(): void
    {
        $completed = ['status' => 'Completed', 'completedAt' => '2026-11-24T10:00:00Z'] + Fixtures::load('todo');
        $this->http
            ->answer(200, Fixtures::load('todo'), ['ETag' => '"v1"'])
            ->answer(200, $completed, ['ETag' => '"v2"'])
            ->answer(204, '');
        $todos = $this->avaro()->todos()->todos();

        $todo = $todos->get('01JD2Q6R8Y4V8N3K5T7W9X1Z2A');
        self::assertSame('"v1"', $todo->etag);

        $changed = $todos->update($todo->id, Changes::new()->complete()->note(null), $todo->etag);
        $request = $this->http->last();
        self::assertSame(['PATCH', 'application/merge-patch+json', '"v1"'], [$request->getMethod(), $request->getHeaderLine('Content-Type'), $request->getHeaderLine('If-Match')]);
        self::assertSame(['note' => null, 'status' => 'Completed'], $this->http->lastJson());
        self::assertSame([Todo::COMPLETED, '"v2"'], [$changed->status, $changed->etag]);

        $todos->delete($todo->id);
        self::assertSame(['DELETE', 'https://app.avaro.test/rest/v3/todos/todos/01JD2Q6R8Y4V8N3K5T7W9X1Z2A'], [$this->http->last()->getMethod(), (string) $this->http->last()->getUri()]);
    }

    public function testChangesNeedAFieldAndDueNullClearsTheDate(): void
    {
        self::assertSame(['dueDate' => null], Changes::new()->due(null)->toArray());
        self::assertSame(['dueTime' => '09:00', 'dueDate' => '2026-11-30', 'assignee' => '53'], Changes::new()->assignee('53')->due('2026-11-30', '09:00')->toArray());
        self::assertSame(['address' => ['id' => '84927']], Changes::new()->address('84927')->toArray());

        $this->expectException(LogicException::class);
        Changes::new()->toArray();
    }

    public function testStaleChangesAndTakenSourceKeysThrow(): void
    {
        $this->http
            ->answer(412, ['type' => 'https://app.avaro.test/rest/v3/problems/precondition-failed', 'title' => 'The task was changed meanwhile.', 'status' => 412])
            ->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/source-key-taken', 'title' => 'Another open task holds the sourceKey of this task.', 'status' => 409]);
        $todos = $this->avaro()->todos()->todos();

        try {
            $todos->update('01JD2Q6R8Y4V8N3K5T7W9X1Z2A', Changes::new()->title('Neu'), '"old"');
            self::fail('No exception.');
        } catch (PreconditionFailed $error) {
            self::assertSame('precondition-failed', $error->slug());
        }

        try {
            $todos->update('01JD2Q6R8Y4V8N3K5T7W9X1Z2A', Changes::new()->reopen());
            self::fail('No exception.');
        } catch (Conflict $error) {
            self::assertSame('source-key-taken', $error->slug());
        }
    }

    public function testAnAddressReferenceCarriesTheCustomerNumber(): void
    {
        $this->http
            ->answer(200, ['data' => [], 'next' => null])
            ->answer(201, Fixtures::load('todo'));
        $todos = $this->avaro()->todos()->todos();
        $company = new AddressReference('ExamplePlatform', '01HXB0COMPANY0000000000000', '10042');

        $todos->list(assignee: TodoList::ALL, address: $company);
        parse_str($this->http->last()->getUri()->getQuery(), $query);
        self::assertSame(['assignee' => 'All', 'addressReference' => 'ExamplePlatform:01HXB0COMPANY0000000000000', 'addressCustomerNumber' => '10042'], $query);

        $todos->create(new NewTodo('Rückruf', address: $company));
        self::assertSame(['reference' => ['system' => 'ExamplePlatform', 'externalId' => '01HXB0COMPANY0000000000000'], 'customerNumber' => '10042'], $this->http->lastJson()['address']);
        self::assertSame(['address' => ['reference' => ['system' => 'ExamplePlatform', 'externalId' => 'x']]], Changes::new()->address(new AddressReference('ExamplePlatform', 'x'))->toArray());
    }

    public function testAssignees(): void
    {
        $this->http->answer(200, ['data' => [['id' => '53', 'name' => 'Mara Stein', 'references' => []]], 'next' => null]);

        $page = $this->avaro()->todos()->assignees(limit: 50);

        self::assertSame('https://app.avaro.test/rest/v3/todos/assignees?limit=50', (string) $this->http->last()->getUri());
        self::assertSame('Mara Stein', $page->items[0]->name);
    }
}
