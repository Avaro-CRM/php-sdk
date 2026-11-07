<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Auth\ApiKey;
use Avaro\Sdk\Auth\Credentials;
use Avaro\Sdk\Avaro;
use Avaro\Sdk\Tests\Support\FakeHttp;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase as Base;

abstract class TestCase extends Base
{
    protected FakeHttp $http;

    protected function setUp(): void
    {
        $this->http = new FakeHttp();
    }

    protected function avaro(?Credentials $credentials = null): Avaro
    {
        $factory = new Psr17Factory();

        return Avaro::create('https://app.avaro.test', $credentials ?? new ApiKey('avk_0123456789abcdef0123_secret'), $this->http, $factory, $factory);
    }
}
