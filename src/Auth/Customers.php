<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use Avaro\Sdk\Http\Resource;

/**
 * Sign-in, profile and password of a contact (scope auth.customers:login).
 */
final class Customers extends Resource
{
    /**
     * @throws \Avaro\Sdk\Exception\Unauthorized   email or password wrong
     * @throws \Avaro\Sdk\Exception\TooManyRequests after five failures
     */
    public function login(string $email, #[\SensitiveParameter] string $password): CustomerLogin
    {
        $data = $this->transport->send('POST', '/auth/customers/login', json: ['email' => $email, 'password' => $password])->data();

        return new CustomerLogin($data->string('token'), $data->dateTime('expiresAt'), Customer::fromData($data->data('customer')));
    }

    public function logout(): void
    {
        $this->transport->send('POST', '/auth/customers/logout', customerToken: $this->customer());
    }

    public function me(): Customer
    {
        return Customer::fromData($this->transport->send('GET', '/auth/customers/me', customerToken: $this->customer())->data());
    }

    /**
     * Other customer tokens of the contact end, the current one stays.
     *
     * @throws \Avaro\Sdk\Exception\ValidationFailed code Wrong at /currentPassword
     */
    public function changePassword(#[\SensitiveParameter] string $currentPassword, #[\SensitiveParameter] string $password): void
    {
        $this->transport->send('POST', '/auth/customers/password', json: ['currentPassword' => $currentPassword, 'password' => $password], customerToken: $this->customer());
    }

    /**
     * Avaro mails a link to returnUrl with email and token in its query;
     * the host must be registered at the client.
     */
    public function requestPasswordReset(string $email, string $returnUrl): void
    {
        $this->transport->send('POST', '/auth/customers/password-reset', json: ['email' => $email, 'returnUrl' => $returnUrl]);
    }

    /**
     * @throws \Avaro\Sdk\Exception\Gone the token is unknown, used or expired
     */
    public function confirmPasswordReset(string $email, #[\SensitiveParameter] string $token, #[\SensitiveParameter] string $password): void
    {
        $this->transport->send('POST', '/auth/customers/password-reset/confirm', json: ['email' => $email, 'token' => $token, 'password' => $password]);
    }
}
