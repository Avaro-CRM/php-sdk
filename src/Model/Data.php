<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Model;

use Avaro\Sdk\Exception\UnexpectedResponse;
use DateTimeImmutable;
use Exception;

/**
 * Typed access to a JSON object of a response; a missing or wrong value
 * throws UnexpectedResponse with the path of the field.
 */
final class Data
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        public readonly array $values,
        private readonly string $path = '',
    ) {}

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->values) && null !== $this->values[$key];
    }

    public function string(string $key): string
    {
        $value = $this->values[$key] ?? null;

        return \is_string($value) ? $value : throw $this->wrong($key, 'a string');
    }

    public function optString(string $key): ?string
    {
        $value = $this->values[$key] ?? null;

        return null === $value || \is_string($value) ? $value : throw $this->wrong($key, 'a string or null');
    }

    public function int(string $key): int
    {
        $value = $this->values[$key] ?? null;

        return \is_int($value) ? $value : throw $this->wrong($key, 'an integer');
    }

    public function optInt(string $key): ?int
    {
        $value = $this->values[$key] ?? null;

        return null === $value || \is_int($value) ? $value : throw $this->wrong($key, 'an integer or null');
    }

    public function number(string $key): int|float
    {
        $value = $this->values[$key] ?? null;

        return \is_int($value) || \is_float($value) ? $value : throw $this->wrong($key, 'a number');
    }

    public function bool(string $key, ?bool $default = null): bool
    {
        $value = $this->values[$key] ?? $default;

        return \is_bool($value) ? $value : throw $this->wrong($key, 'a boolean');
    }

    public function data(string $key): self
    {
        return $this->optData($key) ?? throw $this->wrong($key, 'an object');
    }

    public function optData(string $key): ?self
    {
        $value = $this->values[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_array($value) || ([] !== $value && array_is_list($value))) {
            throw $this->wrong($key, 'an object');
        }

        /** @var array<string, mixed> $value */
        return new self($value, $this->path . '/' . $key);
    }

    /**
     * @return list<self>
     */
    public function list(string $key): array
    {
        return $this->optList($key) ?? throw $this->wrong($key, 'a list');
    }

    /**
     * @return list<self>|null
     */
    public function optList(string $key): ?array
    {
        $value = $this->values[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_array($value) || !array_is_list($value)) {
            throw $this->wrong($key, 'a list');
        }

        $items = [];

        foreach ($value as $index => $item) {
            if (!\is_array($item) || ([] !== $item && array_is_list($item))) {
                throw $this->wrong($key . '/' . $index, 'an object');
            }

            /** @var array<string, mixed> $item */
            $items[] = new self($item, $this->path . '/' . $key . '/' . $index);
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public function strings(string $key): array
    {
        $value = $this->values[$key] ?? [];

        if (!\is_array($value) || !array_is_list($value)) {
            throw $this->wrong($key, 'a list of strings');
        }

        foreach ($value as $item) {
            if (!\is_string($item)) {
                throw $this->wrong($key, 'a list of strings');
            }
        }

        /** @var list<string> $value */
        return $value;
    }

    public function dateTime(string $key): DateTimeImmutable
    {
        return $this->optDateTime($key) ?? throw $this->wrong($key, 'a date');
    }

    public function optDateTime(string $key): ?DateTimeImmutable
    {
        $value = $this->optString($key);

        if (null === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            throw $this->wrong($key, 'a date');
        }
    }

    private function wrong(string $key, string $expected): UnexpectedResponse
    {
        return new UnexpectedResponse(\sprintf('The field "%s" of the response is not %s.', $this->path . '/' . $key, $expected));
    }
}
