<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Exception\TooManyRequests;
use Avaro\Sdk\Exception\Unauthorized;

final class AccountTest extends TestCase
{
    public function testSignInAndOut(): void
    {
        $this->http
            ->answer(200, ['token' => 'avc_abc', 'expiresAt' => '2026-12-07T10:00:00Z', 'customer' => \Avaro\Sdk\Tests\Support\Fixtures::load('customer')])
            ->answer(204, '');
        $avaro = $this->avaro();

        $login = $avaro->auth()->customers()->login('erika@example.test', 'geheim-geheim');

        self::assertSame(['email' => 'erika@example.test', 'password' => 'geheim-geheim'], $this->http->lastJson());
        self::assertSame('avc_abc', $login->token);
        self::assertSame('10001', $login->customer->customerNumber);
        self::assertSame('a1', $login->customer->billingAddress?->id);
        self::assertCount(2, $login->customer->deliveryAddresses);

        $customer = $avaro->withCustomer($login->token);
        self::assertSame('avc_abc', $customer->customerToken());
        self::assertNull($avaro->customerToken());
        $customer->auth()->customers()->logout();
        self::assertSame('avc_abc', $this->http->last()->getHeaderLine('X-Customer-Token'));
        self::assertSame('https://app.avaro.test/rest/v3/auth/customers/logout', (string) $this->http->last()->getUri());
    }

    public function testWrongPasswordsAreCounted(): void
    {
        $this->http
            ->answer(401, ['type' => 'https://app.avaro.test/rest/v3/problems/invalid-login', 'title' => 'Email or password are wrong.', 'status' => 401])
            ->answer(429, ['type' => 'https://app.avaro.test/rest/v3/problems/too-many-requests', 'title' => 'Too many attempts', 'status' => 429], ['Retry-After' => '120']);
        $customers = $this->avaro()->auth()->customers();

        try {
            $customers->login('erika@example.test', 'falsch');
            self::fail('No exception.');
        } catch (Unauthorized $error) {
            self::assertSame('invalid-login', $error->slug());
        }

        try {
            $customers->login('erika@example.test', 'falsch');
            self::fail('No exception.');
        } catch (TooManyRequests $error) {
            self::assertSame(120, $error->retryAfter);
        }
    }

    public function testProfileAndPasswords(): void
    {
        $this->http->fixture('customer')->answer(204, '')->answer(202, '')->answer(204, '');
        $customers = $this->avaro()->withCustomer('avc_1')->auth()->customers();

        self::assertSame('Erika Muster', $customers->me()->name);

        $customers->changePassword('alt-alt-alt', 'neu-neu-neu-neu');
        self::assertSame(['currentPassword' => 'alt-alt-alt', 'password' => 'neu-neu-neu-neu'], $this->http->lastJson());

        $customers->requestPasswordReset('erika@example.test', 'https://shop.example.test/anmeldung');
        self::assertSame('', $this->http->last()->getHeaderLine('X-Customer-Token'));
        self::assertSame(['email' => 'erika@example.test', 'returnUrl' => 'https://shop.example.test/anmeldung'], $this->http->lastJson());

        $customers->confirmPasswordReset('erika@example.test', 'tok', 'neu-neu-neu-neu');
        self::assertSame('https://app.avaro.test/rest/v3/auth/customers/password-reset/confirm', (string) $this->http->last()->getUri());
    }

    public function testOrdersAndInvoices(): void
    {
        $this->http->fixture('orders')->fixture('order')->fixture('invoices')
            ->answer(200, '%PDF-1.7 …', ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Rechnung-2026-01234.pdf"']);
        $avaro = $this->avaro()->withCustomer('avc_1');

        $orders = $avaro->orders()->list(limit: 10);
        self::assertSame('Open', $orders->items[0]->status);
        self::assertSame('https://app.avaro.test/rest/v3/orders/orders?limit=10', (string) $this->http->last()->getUri());

        $order = $avaro->orders()->get($orders->items[0]->id);
        self::assertSame('A-2026-0815', $order->summary->number);
        self::assertSame('Hof 2', $order->deliveryAddress->street);
        self::assertNull($order->deliveryAddress->country);
        self::assertSame(['inv-1'], $order->invoiceIds);

        $invoices = $avaro->invoices()->list();
        self::assertSame('c2', $invoices->next);
        self::assertSame('2026-11-22', $invoices->items[0]->dueDate?->format('Y-m-d'));

        $pdf = $avaro->invoices()->pdf('inv-1');
        self::assertSame('Rechnung-2026-01234.pdf', $pdf->fileName);
        self::assertSame('%PDF-1.7 …', $pdf->contents);
        self::assertSame('https://app.avaro.test/rest/v3/invoices/invoices/inv-1/pdf', (string) $this->http->last()->getUri());
    }
}
