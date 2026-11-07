<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Http;

use Avaro\Sdk\Exception\UnexpectedResponse;
use Avaro\Sdk\Model\Data;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * A response of the API, read once.
 */
final class Result
{
    /**
     * @param array<string, string> $headers names in lower case
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
    ) {}

    public static function of(ResponseInterface $response): self
    {
        $headers = [];

        foreach (array_keys($response->getHeaders()) as $name) {
            $headers[strtolower((string) $name)] = $response->getHeaderLine((string) $name);
        }

        return new self($response->getStatusCode(), $headers, (string) $response->getBody());
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * The body as JSON object.
     */
    public function data(): Data
    {
        try {
            $decoded = json_decode($this->body, true, 64, \JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new UnexpectedResponse(\sprintf('The API answered %d without valid JSON.', $this->status), $this->status, null, $error);
        }

        if (!\is_array($decoded) || ([] !== $decoded && array_is_list($decoded))) {
            throw new UnexpectedResponse(\sprintf('The API answered %d without a JSON object.', $this->status), $this->status);
        }

        /** @var array<string, mixed> $decoded */
        return new Data($decoded);
    }
}
