<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Exception\Gone;
use Avaro\Sdk\Exception\NotFound;
use Avaro\Sdk\Exception\QuoteInvalid;
use Avaro\Sdk\Exception\TotalChanged;
use Avaro\Sdk\Exception\ValidationFailed;
use Avaro\Sdk\Shop\AvailabilityStatus;
use Avaro\Sdk\Shop\CatalogEntry;
use Avaro\Sdk\Shop\Consent;
use Avaro\Sdk\Shop\Line;
use Avaro\Sdk\Shop\OrderRequest;
use Avaro\Sdk\Shop\PriceDisplay;
use Avaro\Sdk\Shop\QuoteRequest;
use Avaro\Sdk\Tests\Support\Fixtures;
use InvalidArgumentException;
use LogicException;

final class ShopTest extends TestCase
{
    public function testTheChannel(): void
    {
        $this->http->fixture('channel');

        $channel = $this->avaro()->withCustomer('avc_1')->shop()->channel();

        self::assertSame('avc_1', $this->http->last()->getHeaderLine('X-Customer-Token'));
        self::assertSame(PriceDisplay::Net, $channel->priceDisplay);
        self::assertFalse($channel->pricesPublic);
        self::assertSame(15000, $channel->minimumOrderNet);
        self::assertSame('Invoice', $channel->paymentMethods[0]->key);
        self::assertTrue($channel->legal[0]->required);
        self::assertFalse($channel->legal[1]->required);
    }

    public function testTheCategoryTree(): void
    {
        $this->http->fixture('categories');

        $tree = $this->avaro()->shop()->categories();

        self::assertSame(['Kakteen', 'Zubehör'], array_map(static fn($category): string => $category->title, $tree));
        self::assertSame('echinocactus', $tree[0]->children[0]->slug);
        self::assertNull($tree[0]->children[0]->description);
        self::assertSame(800, $tree[1]->image?->width);
    }

    public function testProductsPageByPage(): void
    {
        $second = Fixtures::load('products');
        $second['data'] = [];
        $second['next'] = null;
        $this->http->fixture('products');

        $page = $this->avaro()->withCustomer('avc_1')->shop()->products()->list(category: 'c2', limit: 2);
        self::assertSame('https://app.avaro.test/rest/v3/shop/products?category=c2&limit=2', (string) $this->http->last()->getUri());
        self::assertCount(2, $page);
        self::assertSame('cursor-2', $page->next);

        [$cactus, $soil] = $page->items;
        self::assertSame('12.4500', $cactus->price?->unit->net);
        self::assertSame('13.9000', $cactus->price->regularUnit?->net);
        self::assertSame(24, $cactus->price->tiers[0]->minQuantity);
        self::assertSame('24.9900', $cactus->price->retailLabel?->retailPrice);
        self::assertSame(AvailabilityStatus::Available, $cactus->availability->status);
        // A status the SDK does not know counts as unknown.
        self::assertSame(AvailabilityStatus::Unknown, $soil->availability->status);
        self::assertNull($soil->price);

        $this->http->fixture('products')->answer(200, $second);
        $titles = [];

        foreach ($this->avaro()->shop()->products()->each() as $product) {
            $titles[] = $product->title;
        }

        self::assertSame(['Echinocactus grusonii', 'Kakteenerde'], $titles);
        self::assertSame('https://app.avaro.test/rest/v3/shop/products?limit=100&cursor=cursor-2', (string) $this->http->last()->getUri());
    }

    public function testOneProduct(): void
    {
        $this->http->fixture('product')->answer(404, ['type' => 'https://app.avaro.test/rest/v3/problems/not-found', 'title' => 'Not found', 'status' => 404]);
        $products = $this->avaro()->shop()->products();

        $product = $products->get('echinocactus grusonii/123');

        self::assertSame('https://app.avaro.test/rest/v3/shop/products/echinocactus%20grusonii%2F123', (string) $this->http->last()->getUri());
        self::assertSame(6, $product->quantityStep);
        self::assertCount(2, $product->images);
        self::assertSame('Blüte', $product->images[1]->alt);
        self::assertSame(['startseite'], $product->tags);
        self::assertSame('2026-11-06T09:30:00+00:00', $product->updatedAt?->format(\DATE_ATOM));

        $this->expectException(NotFound::class);
        $products->get('nope');
    }

    public function testChangesForTheSync(): void
    {
        $this->http->fixture('changes')->answer(410, ['type' => 'https://app.avaro.test/rest/v3/problems/since-expired', 'title' => 'Gone', 'status' => 410]);

        $page = $this->avaro()->shop()->changes('pos-1');

        self::assertSame('https://app.avaro.test/rest/v3/shop/changes?since=pos-1', (string) $this->http->last()->getUri());
        self::assertSame(CatalogEntry::Category, $page->changes[0]->type);
        self::assertTrue($page->changes[2]->deleted);
        self::assertNull($page->next);
        self::assertSame('pos-42', $page->until);

        try {
            $this->avaro()->shop()->changes('pos-0');
            self::fail('No exception.');
        } catch (Gone $error) {
            self::assertSame('since-expired', $error->slug());
        }
    }

    public function testAQuoteNeedsACustomer(): void
    {
        $this->expectException(LogicException::class);

        $this->avaro()->shop()->quotes()->create(new QuoteRequest([new Line('123', 12)]));
    }

    public function testAQuote(): void
    {
        $this->http->fixture('quote');

        $quote = $this->avaro()->withCustomer('avc_1')->shop()->quotes()->create(new QuoteRequest([new Line('123', 12), new Line('777', 1)], shippingMethod: 'Parcel'));

        self::assertSame(['lines' => [['productId' => '123', 'quantity' => 12], ['productId' => '777', 'quantity' => 1]], 'deliveryAddressId' => null, 'shippingMethod' => 'Parcel'], $this->http->lastJson());
        self::assertFalse($quote->valid);
        self::assertSame(['NotFound'], $quote->lines[1]->problems);
        self::assertNull($quote->lines[1]->total);
        self::assertSame(60, $quote->minimumOrder->missing);
        self::assertSame(10000, $quote->shippingMethods[0]->freeFrom);
        self::assertSame('7.00', $quote->taxes[0]->rate);
    }

    public function testPlacingAnOrder(): void
    {
        $this->http->fixture('order-summary', 201)->fixture('order-summary', 200);
        $orders = $this->avaro()->withCustomer('avc_1')->shop()->orders();
        $order = new OrderRequest([new Line('123', 12)], 'OwnDelivery', 'Invoice', [new Consent('Terms', '2026-10-01')], 15986, reference: 'Frühjahr');

        $placed = $orders->place($order, 'checkout-0001');

        self::assertFalse($placed->repeated);
        self::assertSame('A-2026-0815', $placed->order->number);
        self::assertSame('checkout-0001', $this->http->last()->getHeaderLine('Idempotency-Key'));
        self::assertSame(['gross' => 15986], $this->http->lastJson()['expectedTotal']);
        self::assertSame([['key' => 'Terms', 'version' => '2026-10-01']], $this->http->lastJson()['consents']);
        self::assertTrue($orders->place($order, 'checkout-0001')->repeated);
    }

    public function testAnOrderWithAChangedTotalCarriesTheNewQuote(): void
    {
        $problem = ['type' => 'https://app.avaro.test/rest/v3/problems/total-changed', 'title' => 'The total changed', 'status' => 409, 'quote' => Fixtures::load('quote')];
        $this->http->answer(409, $problem)->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/quote-invalid', 'title' => 'Invalid', 'status' => 409, 'quote' => Fixtures::load('quote')]);
        $orders = $this->avaro()->withCustomer('avc_1')->shop()->orders();
        $order = new OrderRequest([new Line('123', 12)], 'OwnDelivery', 'Invoice', [], 1);

        try {
            $orders->place($order, 'checkout-0002');
            self::fail('No exception.');
        } catch (TotalChanged $error) {
            self::assertSame(15986, $error->quote?->total->gross);
        }

        $this->expectException(QuoteInvalid::class);
        $orders->place($order, 'checkout-0003');
    }

    public function testValidationErrorsNameTheirFields(): void
    {
        $this->http->answer(422, ['type' => 'https://app.avaro.test/rest/v3/problems/validation', 'title' => 'Invalid', 'status' => 422, 'errors' => [
            ['pointer' => '/consents', 'detail' => 'Agree to the terms.', 'code' => 'ConsentMissing'],
            ['pointer' => '/paymentMethod', 'detail' => 'Not allowed.', 'code' => 'NotAllowed'],
        ]]);

        try {
            $this->avaro()->withCustomer('avc_1')->shop()->orders()->place(new OrderRequest([new Line('123', 12)], 'OwnDelivery', 'Cash', [], 1), 'checkout-0004');
            self::fail('No exception.');
        } catch (ValidationFailed $error) {
            self::assertSame(['/consents' => 'Agree to the terms.', '/paymentMethod' => 'Not allowed.'], $error->messages());
            self::assertTrue($error->has('/consents', 'ConsentMissing'));
            self::assertFalse($error->has('/lines'));
        }
    }

    public function testAnIdempotencyKeyIsChecked(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->avaro()->withCustomer('avc_1')->shop()->orders()->place(new OrderRequest([new Line('123', 6)], 'OwnDelivery', 'Invoice', [], 1), 'short');
    }
}
