<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Exception\Forbidden;
use Avaro\Sdk\Exception\ServerError;
use Avaro\Sdk\Exception\TransportFailed;
use Avaro\Sdk\Exception\UnexpectedResponse;
use Avaro\Sdk\Tests\Support\FakeHttp;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class ErrorsTest extends TestCase
{
    public function testAServerErrorCarriesItsDiagnosticId(): void
    {
        $this->http->answer(500, ['type' => 'https://app.avaro.test/rest/v3/problems/internal-error', 'title' => 'Internal error', 'status' => 500, 'diagnosticId' => 'd-42']);

        try {
            $this->avaro()->shop()->channel();
            self::fail('No exception.');
        } catch (ServerError $error) {
            self::assertSame('d-42', $error->problem->diagnosticId);
            self::assertSame(500, $error->status());
        }
    }

    public function testAMissingScopeIsForbidden(): void
    {
        $this->http->answer(403, ['type' => 'https://app.avaro.test/rest/v3/problems/missing-scope', 'title' => 'Forbidden', 'status' => 403, 'detail' => 'shop.orders:write']);

        try {
            $this->avaro()->shop()->channel();
            self::fail('No exception.');
        } catch (Forbidden $error) {
            self::assertSame('missing-scope', $error->slug());
            self::assertSame('Forbidden: shop.orders:write', $error->getMessage());
        }
    }

    public function testAnswersThatAreNoJson(): void
    {
        $this->http->answer(502, '<html>Bad gateway</html>')->answer(200, 'not json')->answer(200, ['name' => 1]);

        try {
            $this->avaro()->shop()->channel();
            self::fail('No exception.');
        } catch (ServerError $error) {
            self::assertSame('HTTP 502', $error->problem->title);
        }

        try {
            $this->avaro()->shop()->channel();
            self::fail('No exception.');
        } catch (UnexpectedResponse $error) {
            self::assertStringContainsString('without valid JSON', $error->getMessage());
        }

        $this->expectException(UnexpectedResponse::class);
        $this->expectExceptionMessage('"/name"');
        $this->avaro()->shop()->channel();
    }

    public function testANetworkErrorIsATransportFailure(): void
    {
        $http = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new class('Connection refused') extends RuntimeException implements ClientExceptionInterface {};
            }
        };
        $factory = new Psr17Factory();

        $this->expectException(TransportFailed::class);

        \Avaro\Sdk\Avaro::create('https://app.avaro.test/rest/v3/', new \Avaro\Sdk\Auth\ApiKey('k'), $http, $factory, $factory)->shop()->channel();
    }

    public function testTheBaseUrlMayNameTheVersion(): void
    {
        $http = new FakeHttp();
        $http->fixture('channel');
        $factory = new Psr17Factory();

        \Avaro\Sdk\Avaro::create('https://app.avaro.test/rest/v3/', new \Avaro\Sdk\Auth\ApiKey('k'), $http, $factory, $factory, 'en')->shop()->channel();

        self::assertSame('https://app.avaro.test/rest/v3/shop/channel', (string) $http->last()->getUri());
        self::assertSame('en', $http->last()->getHeaderLine('Accept-Language'));
    }
}
