<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Company;

use Avaro\Sdk\Http\Resource;

/**
 * Users by external ID. provision() creates a user of the client's own
 * system once (login by email, no password, employee with the client's
 * default role if the tenant chose one) and keeps its profile up to date;
 * calling it again changes nothing more. An email of an employee of the
 * tenant is never taken over: Avaro records a link request the tenant
 * approves (UserLinkRequested).
 */
final class Users extends Resource
{
    /**
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function get(string $system, string $externalId): User
    {
        return User::fromData($this->transport->send('GET', self::path($system, $externalId))->data());
    }

    /**
     * @throws \Avaro\Sdk\Exception\UserLinkRequested slug user-link-requested: an employee of the tenant has the email; a tenant admin approves the link in Avaro, then repeat the call
     * @throws \Avaro\Sdk\Exception\UserLinkRejected  slug user-link-rejected: the tenant rejected linking this ID
     * @throws \Avaro\Sdk\Exception\Conflict          with slug user-email-taken when another Avaro user has the email and cannot be linked (e.g. of another tenant), or busy
     * @throws \Avaro\Sdk\Exception\Forbidden        when system is not the client's external system
     * @throws \Avaro\Sdk\Exception\ValidationFailed
     */
    public function provision(string $system, string $externalId, UserProfile $profile): User
    {
        $result = $this->transport->send('PUT', self::path($system, $externalId), json: $profile->toArray());

        return User::fromData($result->data(), 201 === $result->status);
    }

    private static function path(string $system, string $externalId): string
    {
        return '/company/users/by-external-id/' . self::segment($system) . '/' . self::segment($externalId);
    }
}
