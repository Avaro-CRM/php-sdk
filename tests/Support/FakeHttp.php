<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * A PSR-18 client with answers in a queue; keeps every request.
 */
final class FakeHttp implements ClientInterface
{
    /** @var list<ResponseInterface> */
    private array $responses = [];

    /** @var list<RequestInterface> */
    public array $requests = [];

    /**
     * @param array<mixed>|string  $body
     * @param array<string, string> $headers
     */
    public function answer(int $status, array|string $body = [], array $headers = []): self
    {
        $type = \is_string($body) ? [] : ['Content-Type' => $status >= 400 ? 'application/problem+json' : 'application/json'];
        $this->responses[] = new Response($status, $headers + $type, \is_string($body) ? $body : json_encode($body, \JSON_THROW_ON_ERROR));

        return $this;
    }

    public function fixture(string $name, int $status = 200): self
    {
        return $this->answer($status, Fixtures::load($name));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return array_shift($this->responses) ?? throw new RuntimeException('No answer for ' . $request->getMethod() . ' ' . $request->getUri());
    }

    public function last(): RequestInterface
    {
        return $this->requests[\count($this->requests) - 1] ?? throw new RuntimeException('No request.');
    }

    /**
     * @return array<mixed>
     */
    public function lastJson(): array
    {
        $decoded = json_decode((string) $this->last()->getBody(), true, 64, \JSON_THROW_ON_ERROR);

        return \is_array($decoded) ? $decoded : throw new RuntimeException('No JSON object.');
    }
}
