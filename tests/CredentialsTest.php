<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Auth\BasicAuth;
use Avaro\Sdk\Auth\BearerToken;
use Avaro\Sdk\Auth\ClientCredentials;
use Avaro\Sdk\Auth\ClientToken;
use Avaro\Sdk\Auth\TokenStore;
use Avaro\Sdk\Exception\Unauthorized;

final class CredentialsTest extends TestCase
{
    public function testAnApiKeyGoesInItsHeader(): void
    {
        $this->http->fixture('channel');

        $this->avaro()->shop()->channel();

        $request = $this->http->last();
        self::assertSame('https://app.avaro.test/rest/v3/shop/channel', (string) $request->getUri());
        self::assertSame('avk_0123456789abcdef0123_secret', $request->getHeaderLine('X-API-KEY'));
        self::assertSame('de', $request->getHeaderLine('Accept-Language'));
        self::assertStringStartsWith('avaro-php-sdk/', $request->getHeaderLine('User-Agent'));
        self::assertSame('', $request->getHeaderLine('X-Customer-Token'));
    }

    public function testBasicAuthAndBearerToken(): void
    {
        $this->http->fixture('channel')->fixture('channel');

        $this->avaro(new BasicAuth('client', 'secret'))->shop()->channel();
        self::assertSame('Basic ' . base64_encode('client:secret'), $this->http->last()->getHeaderLine('Authorization'));

        $this->avaro(new BearerToken('avt_abc'))->shop()->channel();
        self::assertSame('Bearer avt_abc', $this->http->last()->getHeaderLine('Authorization'));
    }

    public function testClientCredentialsKeepTheTokenUntilShortlyBeforeItEnds(): void
    {
        $credentials = new ClientCredentials('client', 'secret', 'shop.catalog:read');
        $this->http
            ->answer(200, ['access_token' => 'avt_1', 'token_type' => 'Bearer', 'expires_in' => 900, 'scope' => 'shop.catalog:read'])
            ->fixture('channel')
            ->fixture('channel');
        $avaro = $this->avaro($credentials);

        $avaro->shop()->channel();
        $avaro->shop()->channel();

        self::assertCount(3, $this->http->requests);
        $token = $this->http->requests[0];
        self::assertSame('POST', $token->getMethod());
        self::assertSame('https://app.avaro.test/rest/v3/auth/token', (string) $token->getUri());
        self::assertSame('application/x-www-form-urlencoded', $token->getHeaderLine('Content-Type'));
        self::assertSame('grant_type=client_credentials&scope=shop.catalog%3Aread', (string) $token->getBody());
        self::assertSame('Basic ' . base64_encode('client:secret'), $token->getHeaderLine('Authorization'));
        self::assertSame('Bearer avt_1', $this->http->requests[2]->getHeaderLine('Authorization'));
    }

    public function testAnExpiringTokenIsRenewedAndStored(): void
    {
        $store = new class implements TokenStore {
            /** @var array<string, ClientToken> */
            public array $tokens = [];

            public function get(string $key): ?ClientToken
            {
                return $this->tokens[$key] ?? null;
            }

            public function put(string $key, ClientToken $token): void
            {
                $this->tokens[$key] = $token;
            }
        };
        $this->http
            ->answer(200, ['access_token' => 'avt_1', 'token_type' => 'Bearer', 'expires_in' => 30, 'scope' => ''])
            ->fixture('channel')
            ->answer(200, ['access_token' => 'avt_2', 'token_type' => 'bearer', 'expires_in' => 900, 'scope' => ''])
            ->fixture('channel')
            ->fixture('channel');

        // Thirty seconds are within the margin: the next call gets a new one.
        $this->avaro(new ClientCredentials('client', 'secret', store: $store))->shop()->channel();
        $this->avaro(new ClientCredentials('client', 'secret', store: $store))->shop()->channel();
        // A new process finds it in the store.
        $this->avaro(new ClientCredentials('client', 'secret', store: $store))->shop()->channel();

        self::assertCount(5, $this->http->requests);
        self::assertSame('Bearer avt_2', $this->http->last()->getHeaderLine('Authorization'));
        self::assertCount(1, $store->tokens);
    }

    public function testAWrongSecretIsUnauthorized(): void
    {
        $this->http->answer(401, ['error' => 'invalid_client', 'error_description' => 'Unknown client.']);

        try {
            $this->avaro(new ClientCredentials('client', 'wrong'))->shop()->channel();
            self::fail('No exception.');
        } catch (Unauthorized $error) {
            self::assertSame('invalid_client', $error->slug());
            self::assertSame('invalid_client: Unknown client.', $error->getMessage());
        }
    }
}
