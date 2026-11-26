<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Tests\Support\Fixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as Base;
use Symfony\Component\Yaml\Yaml;

/**
 * Compares the SDK with the OpenAPI files of API v3, when
 * AVARO_API_V3_DIR points to them: every call exists, and every fixture
 * has the required fields of its schema.
 */
final class ContractTest extends Base
{
    /** The calls of the SDK: method, path, file. */
    private const CALLS = [
        ['POST', '/auth/token', 'auth'],
        ['POST', '/auth/customers/login', 'auth'],
        ['POST', '/auth/customers/logout', 'auth'],
        ['GET', '/auth/customers/me', 'auth'],
        ['POST', '/auth/customers/password', 'auth'],
        ['POST', '/auth/customers/password-reset', 'auth'],
        ['POST', '/auth/customers/password-reset/confirm', 'auth'],
        ['GET', '/shop/channel', 'shop'],
        ['GET', '/shop/categories', 'shop'],
        ['GET', '/shop/products', 'shop'],
        ['GET', '/shop/products/{product}', 'shop'],
        ['GET', '/shop/changes', 'shop'],
        ['POST', '/shop/quotes', 'shop'],
        ['POST', '/shop/orders', 'shop'],
        ['GET', '/orders/orders', 'orders'],
        ['GET', '/orders/orders/{order}', 'orders'],
        ['GET', '/invoices/invoices', 'invoices'],
        ['GET', '/invoices/invoices/{invoice}/pdf', 'invoices'],
        ['GET', '/hotel/channel', 'hotel'],
        ['GET', '/hotel/units', 'hotel'],
        ['GET', '/hotel/units/{unit}', 'hotel'],
        ['GET', '/hotel/units/{unit}/calendar', 'hotel'],
        ['GET', '/hotel/availability', 'hotel'],
        ['POST', '/hotel/quotes', 'hotel'],
        ['POST', '/hotel/requests', 'hotel'],
        ['GET', '/events/channel', 'events'],
        ['GET', '/events/categories', 'events'],
        ['GET', '/events/categories/{category}', 'events'],
        ['GET', '/events/events', 'events'],
        ['GET', '/events/events/{event}', 'events'],
        ['POST', '/events/bookings', 'events'],
        ['GET', '/events/attendees', 'events'],
        ['GET', '/company/addresses/by-external-id/{system}/{externalId}', 'company'],
        ['PUT', '/company/addresses/by-external-id/{system}/{externalId}', 'company'],
        ['PATCH', '/company/addresses/by-external-id/{system}/{externalId}', 'company'],
        ['GET', '/company/address-fields', 'company'],
        ['GET', '/company/address-fields/{field}/tags', 'company'],
        ['GET', '/company/users/by-external-id/{system}/{externalId}', 'company'],
        ['PUT', '/company/users/by-external-id/{system}/{externalId}', 'company'],
        ['GET', '/todos/todos', 'todos'],
        ['POST', '/todos/todos', 'todos'],
        ['GET', '/todos/todos/summary', 'todos'],
        ['GET', '/todos/todos/{todo}', 'todos'],
        ['PATCH', '/todos/todos/{todo}', 'todos'],
        ['DELETE', '/todos/todos/{todo}', 'todos'],
        ['GET', '/todos/assignees', 'todos'],
        ['GET', '/crm/activities', 'crm'],
        ['POST', '/crm/activities', 'crm'],
        ['GET', '/crm/activities/{activity}', 'crm'],
    ];

    /** Fixtures and the schema they follow. */
    private const FIXTURES = [
        'channel' => 'shop.yaml#/components/schemas/Channel',
        'product' => 'shop.yaml#/components/schemas/Product',
        'quote' => 'shop.yaml#/components/schemas/Quote',
        'order-summary' => 'orders.yaml#/components/schemas/OrderSummary',
        'order' => 'orders.yaml#/components/schemas/Order',
        'customer' => 'auth.yaml#/components/schemas/Customer',
        'hotel-channel' => 'hotel.yaml#/components/schemas/Channel',
        'hotel-unit' => 'hotel.yaml#/components/schemas/Unit',
        'hotel-calendar' => 'hotel.yaml#/components/schemas/Calendar',
        'hotel-quote' => 'hotel.yaml#/components/schemas/Quote',
        'hotel-request' => 'hotel.yaml#/components/schemas/BookingRequestSummary',
        'events-channel' => 'events.yaml#/components/schemas/Channel',
        'events-category' => 'events.yaml#/components/schemas/Category',
        'events-event' => 'events.yaml#/components/schemas/Event',
        'events-booking' => 'events.yaml#/components/schemas/BookingSummary',
        'events-event-attendees' => 'events.yaml#/components/schemas/EventAttendees',
        'todo' => 'todos.yaml#/components/schemas/Todo',
        'todo-summary' => 'todos.yaml#/components/schemas/TodoSummary',
        'company-user' => 'company.yaml#/components/schemas/User',
        'company-address' => 'company.yaml#/components/schemas/Address',
        'company-address-expanded' => 'company.yaml#/components/schemas/Address',
        'company-address-field' => 'company.yaml#/components/schemas/AddressFieldDefinition',
        'crm-activity' => 'crm.yaml#/components/schemas/Activity',
    ];

    /** @var array<string, array<mixed>> */
    private array $files = [];

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function calls(): iterable
    {
        foreach (self::CALLS as [$method, $path, $file]) {
            yield $method . ' ' . $path => [$method, $path, $file];
        }
    }

    #[DataProvider('calls')]
    public function testEveryCallIsInTheContract(string $method, string $path, string $file): void
    {
        $paths = $this->file($file . '.yaml')['paths'] ?? [];

        self::assertIsArray($paths);
        self::assertIsArray($paths[$path] ?? null, $path . ' is not in ' . $file . '.yaml');
        self::assertArrayHasKey(strtolower($method), $paths[$path]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function fixtures(): iterable
    {
        foreach (self::FIXTURES as $fixture => $schema) {
            yield $fixture => [$fixture, $schema];
        }
    }

    #[DataProvider('fixtures')]
    public function testFixturesHaveTheRequiredFields(string $fixture, string $schema): void
    {
        $missing = $this->missing(Fixtures::load($fixture), $this->resolve($schema), '');

        self::assertSame([], $missing);
    }

    /**
     * @param array<mixed> $schema
     *
     * @return list<string>
     */
    private function missing(mixed $value, array $schema, string $path): array
    {
        $missing = [];

        foreach (self::items($schema['allOf'] ?? null) as $part) {
            $missing = [...$missing, ...$this->missing($value, $this->schema($part), $path)];
        }

        if (!\is_array($value)) {
            return $missing;
        }

        if (array_is_list($value) && \is_array($schema['items'] ?? null)) {
            foreach ($value as $index => $item) {
                $missing = [...$missing, ...$this->missing($item, $this->schema($schema['items']), $path . '/' . $index)];
            }

            return $missing;
        }

        foreach (self::items($schema['required'] ?? null) as $field) {
            if (\is_string($field) && !\array_key_exists($field, $value)) {
                $missing[] = $path . '/' . $field;
            }
        }

        foreach (self::items($schema['properties'] ?? null) as $name => $property) {
            if (\array_key_exists($name, $value) && \is_array($property) && null !== $value[$name]) {
                $missing = [...$missing, ...$this->missing($value[$name], $this->schema($property), $path . '/' . $name)];
            }
        }

        return $missing;
    }

    /**
     * A schema with its $ref and a oneOf with null resolved.
     *
     * @return array<mixed>
     */
    private function schema(mixed $schema, string $file = 'shop.yaml'): array
    {
        if (!\is_array($schema)) {
            return [];
        }

        if (\is_string($schema['$ref'] ?? null)) {
            return $this->resolve($schema['$ref'], $file);
        }

        foreach (self::items($schema['oneOf'] ?? null) as $option) {
            if (\is_array($option) && 'null' !== ($option['type'] ?? null)) {
                return $this->schema($option, $file);
            }
        }

        return $schema;
    }

    /**
     * @return array<mixed>
     */
    private function resolve(string $ref, string $from = 'shop.yaml'): array
    {
        [$file, $pointer] = array_pad(explode('#', $ref, 2), 2, '');
        $file = '' === $file ? $from : $file;
        $node = $this->file($file);

        foreach (explode('/', trim($pointer, '/')) as $part) {
            $node = \is_array($node) ? ($node[$part] ?? null) : null;
        }

        $node = \is_array($node) ? $node : [];

        // References inside point to the same file unless they name one.
        array_walk_recursive($node, static function (mixed &$item, mixed $key) use ($file): void {
            if ('$ref' === $key && \is_string($item) && str_starts_with($item, '#')) {
                $item = $file . $item;
            }
        });

        return $this->schema($node, $file);
    }

    /**
     * @return array<mixed>
     */
    private static function items(mixed $value): array
    {
        return \is_array($value) ? $value : [];
    }

    /**
     * @return array<mixed>
     */
    private function file(string $name): array
    {
        $dir = getenv('AVARO_API_V3_DIR');

        if (!\is_string($dir) || '' === $dir) {
            self::markTestSkipped('Set AVARO_API_V3_DIR to the OpenAPI files of API v3.');
        }

        $parsed = $this->files[$name] ??= (array) Yaml::parseFile(rtrim($dir, '/') . '/' . $name);

        return $parsed;
    }
}
