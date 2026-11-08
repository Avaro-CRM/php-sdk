<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk;

use Avaro\Sdk\Auth\Auth;
use Avaro\Sdk\Auth\Credentials;
use Avaro\Sdk\Events\Events;
use Avaro\Sdk\Hotel\Hotel;
use Avaro\Sdk\Http\Transport;
use Avaro\Sdk\Invoices\Invoices;
use Avaro\Sdk\Orders\Orders;
use Avaro\Sdk\Shop\Shop;
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
 *
 * Immutable: withCustomer() returns a new client for calls on behalf of
 * a signed-in contact.
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

    public function orders(): Orders
    {
        return new Orders($this->transport, $this->customerToken);
    }

    public function invoices(): Invoices
    {
        return new Invoices($this->transport, $this->customerToken);
    }
}
