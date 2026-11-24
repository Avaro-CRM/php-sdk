<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk;

use Avaro\Sdk\Auth\Auth;
use Avaro\Sdk\Auth\ClientCredentials;
use Avaro\Sdk\Auth\Credentials;
use Avaro\Sdk\Company\Company;
use Avaro\Sdk\Crm\Crm;
use Avaro\Sdk\Events\Events;
use Avaro\Sdk\Hotel\Hotel;
use Avaro\Sdk\Http\Transport;
use Avaro\Sdk\Invoices\Invoices;
use Avaro\Sdk\Orders\Orders;
use Avaro\Sdk\Shop\Shop;
use Avaro\Sdk\Todos\Todos;
use LogicException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The client of the Avaro API v3.
 *
 *     $avaro = Avaro::create('https://app.avaro.de', new ClientCredentials($id, $secret), $http, $factory, $factory);
 *     $products = $avaro->shop()->products()->list(limit: 20);
 *     $login = $avaro->auth()->customers()->login($email, $password);
 *     $orders = $avaro->withCustomer($login->token)->orders()->list();
 *     $quote = $avaro->hotel()->quotes()->create(new Stay($arrival, $departure, new Guests(2), $unitId));
 *     $events = $avaro->events()->events()->list(category: $courseId, available: true);
 *     $avaro->company()->users()->provision('ExamplePlatform', $userId, new UserProfile($email, $first, $last));
 *     $todos = $avaro->asUser($userId)->todos()->todos()->list(assignee: TodoList::ME);
 *
 * Immutable: withCustomer() returns a new client for calls on behalf of
 * a signed-in contact, asUser() one for calls on behalf of a user of the
 * client's external system.
 */
final class Avaro
{
    public const VERSION = '1.0.0';

    public function __construct(
        private readonly Transport $transport,
        private readonly ?string $customerToken = null,
    ) {}

    /**
     * @param string $baseUrl e.g. https://app.avaro.de, with or without /rest/v3
     */
    public static function create(
        string $baseUrl,
        Credentials $credentials,
        ClientInterface $http,
        RequestFactoryInterface $requests,
        StreamFactoryInterface $streams,
        string $language = 'de',
    ): self {
        return new self(new Transport($baseUrl, $credentials, $http, $requests, $streams, $language));
    }

    /**
     * A client acting for the contact with this customer token (avc_…).
     */
    public function withCustomer(string $token): self
    {
        return new self($this->transport, $token);
    }

    /**
     * A client acting for the user with this ID in the client's external
     * system, by token exchange. Needs ClientCredentials.
     */
    public function asUser(string $externalUserId, ?string $scope = null): self
    {
        $credentials = $this->transport->credentials();

        if (!$credentials instanceof ClientCredentials) {
            throw new LogicException('asUser() needs ClientCredentials; pass UserCredentials or a BearerToken to Avaro::create() instead.');
        }

        return new self($this->transport->withCredentials($credentials->forUser($externalUserId, $scope)));
    }

    public function withoutCustomer(): self
    {
        return new self($this->transport);
    }

    public function customerToken(): ?string
    {
        return $this->customerToken;
    }

    public function auth(): Auth
    {
        return new Auth($this->transport, $this->customerToken);
    }

    public function shop(): Shop
    {
        return new Shop($this->transport, $this->customerToken);
    }

    /**
     * Holiday apartments or rooms of a hotel channel; needs no customer.
     */
    public function hotel(): Hotel
    {
        return new Hotel($this->transport);
    }

    /**
     * Courses, their events with free seats and bookings of an events
     * channel; needs no customer.
     */
    public function events(): Events
    {
        return new Events($this->transport);
    }

    /**
     * Users of the tenant by their ID in the client's external system;
     * needs the client itself, not a user.
     */
    public function company(): Company
    {
        return new Company($this->transport);
    }

    /**
     * Todos of the tenant; every call acts for a user (asUser() or an
     * employee BearerToken).
     */
    public function todos(): Todos
    {
        return new Todos($this->transport);
    }

    /**
     * CRM activities of addresses; every call acts for a user (asUser() or an
     * employee token).
     */
    public function crm(): Crm
    {
        return new Crm($this->transport);
    }

    public function orders(): Orders
    {
        return new Orders($this->transport, $this->customerToken);
    }

    public function invoices(): Invoices
    {
        return new Invoices($this->transport, $this->customerToken);
    }
}
