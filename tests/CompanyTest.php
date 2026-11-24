<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Company\User;
use Avaro\Sdk\Company\UserProfile;
use Avaro\Sdk\Exception\Conflict;
use Avaro\Sdk\Exception\Forbidden;
use Avaro\Sdk\Tests\Support\Fixtures;

final class CompanyTest extends TestCase
{
    public function testProvisionCreatesOnceAndGetReads(): void
    {
        $this->http
            ->answer(201, Fixtures::load('company-user'))
            ->answer(200, Fixtures::load('company-user'))
            ->answer(200, Fixtures::load('company-user'));
        $users = $this->avaro()->company()->users();
        $profile = new UserProfile('Mara.Stein@example.test', 'Mara', 'Stein');

        $created = $users->provision('ExamplePlatform', '01HXA7Q2Z6V7C1M4K9P0R3S5T8', $profile);
        self::assertSame(['PUT', 'https://app.avaro.test/rest/v3/company/users/by-external-id/ExamplePlatform/01HXA7Q2Z6V7C1M4K9P0R3S5T8'], [$this->http->last()->getMethod(), (string) $this->http->last()->getUri()]);
        self::assertSame(['email' => 'Mara.Stein@example.test', 'firstName' => 'Mara', 'lastName' => 'Stein'], $this->http->lastJson());
        self::assertTrue($created->created);
        self::assertTrue($created->isActive());
        self::assertSame(User::PROVISIONED, $created->origin);
        self::assertSame('01HXA7Q2Z6V7C1M4K9P0R3S5T8', $created->externalIds[0]->externalId);

        self::assertFalse($users->provision('ExamplePlatform', '01HXA7Q2Z6V7C1M4K9P0R3S5T8', $profile)->created);
        self::assertSame('mara.stein@example.test', $users->get('ExamplePlatform', '01HXA7Q2Z6V7C1M4K9P0R3S5T8')->email);
    }

    public function testATakenEmailAndAForeignSystemAreRefused(): void
    {
        $this->http
            ->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/user-email-taken', 'title' => 'Another Avaro user has this email.', 'status' => 409])
            ->answer(403, ['type' => 'https://app.avaro.test/rest/v3/problems/foreign-system', 'title' => 'Not allowed.', 'status' => 403]);
        $users = $this->avaro()->company()->users();

        try {
            $users->provision('ExamplePlatform', 'u-1', new UserProfile('taken@example.test', '', 'Muster'));
            self::fail('No exception.');
        } catch (Conflict $error) {
            self::assertSame('user-email-taken', $error->slug());
        }

        $this->expectException(Forbidden::class);
        $users->provision('OtherSystem', 'u-1', new UserProfile('a@example.test', '', 'Muster'));
    }
}
