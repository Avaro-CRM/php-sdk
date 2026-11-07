<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Http;

use Avaro\Sdk\Auth\Credentials;
use Avaro\Sdk\Avaro;
use Avaro\Sdk\Exception\TransportFailed;
use LogicException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Sends requests to the API: base URL, credentials, language and the
 * mapping of errors to exceptions.
 *
 * @internal
 */
final class Transport
{
    private readonly string $baseUrl;

    public function __construct(
        string $baseUrl,
        private readonly Credentials $credentials,
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface $requests,
        private readonly StreamFactoryInterface $streams,
        private readonly string $language = 'de',
    ) {
        $baseUrl = rtrim($baseUrl, '/');
        $this->baseUrl = str_ends_with($baseUrl, '/rest/v3') ? $baseUrl : $baseUrl . '/rest/v3';
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * A request to the API with the credentials of the client, answered
     * with 2xx or 304; every other status throws.
     *
     * @param array<string, scalar|null> $query
     * @param array<string, mixed>|null  $json
     * @param array<string, string>      $headers
     */
    public function send(string $method, string $path, array $query = [], ?array $json = null, array $headers = [], ?string $customerToken = null): Result
    {
        $request = $this->request($method, $path, $query, $headers);

        if (null !== $json) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streams->createStream(json_encode($json, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRESERVE_ZERO_FRACTION)));
        }

        if (null !== $customerToken) {
            $request = $request->withHeader('X-Customer-Token', $customerToken);
        }

        return $this->dispatch($this->credentials->authenticate($request, $this));
    }

    /**
     * A request without the credentials of the client, e.g. for the token.
     *
     * @param array<string, string> $form
     * @param array<string, string> $headers
     */
    public function sendForm(string $path, array $form, array $headers = []): Result
    {
        $request = $this->request('POST', $path, [], $headers)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streams->createStream(http_build_query($form, '', '&', \PHP_QUERY_RFC3986)));

        return $this->dispatch($request);
    }

    /**
     * @param array<string, scalar|null> $query
     * @param array<string, string>      $headers
     */
    private function request(string $method, string $path, array $query, array $headers): RequestInterface
    {
        if (!str_starts_with($path, '/')) {
            throw new LogicException('A path starts with a slash.');
        }

        $query = array_filter($query, static fn($value): bool => null !== $value && '' !== $value);
        $url = $this->baseUrl . $path . ([] === $query ? '' : '?' . http_build_query($query, '', '&', \PHP_QUERY_RFC3986));
        $request = $this->requests->createRequest($method, $url)
            ->withHeader('Accept', 'application/json, application/problem+json')
            ->withHeader('Accept-Language', $this->language)
            ->withHeader('User-Agent', 'avaro-php-sdk/' . Avaro::VERSION);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    private function dispatch(RequestInterface $request): Result
    {
        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $error) {
            throw new TransportFailed('Avaro could not be reached: ' . $error->getMessage(), 0, $error);
        }

        $result = Result::of($response);

        if ($result->status >= 400 || ($result->status >= 300 && 304 !== $result->status)) {
            throw Errors::fromResult($result);
        }

        return $result;
    }
}
