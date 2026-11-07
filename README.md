# Avaro PHP SDK

PHP client for the Avaro API v3: the catalog of a shop, sign-in of
customers, quotes, orders and invoices. It needs an API client of Avaro;
ask Avaro for one.

- PHP 8.2 or later
- Any HTTP client after PSR-18 and factories after PSR-17, e.g.
  `symfony/http-client` with `nyholm/psr7`, or Guzzle
- Immutable models, money in integer cents and unit prices as decimal
  strings, never floats

## Install

```bash
composer require avaro/sdk
```

## Start

```php
use Avaro\Sdk\Auth\ClientCredentials;
use Avaro\Sdk\Avaro;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$factory = new Psr17Factory();
$avaro = Avaro::create(
    'https://app.avaro.de',
    new ClientCredentials($clientId, $clientSecret),
    new Psr18Client(),
    $factory,
    $factory,
);

$channel = $avaro->shop()->channel();

foreach ($avaro->shop()->products()->each() as $product) {
    echo $product->title, PHP_EOL;
}
```

Clients authenticate with one of `ApiKey` (`avk_…` in `X-API-KEY`),
`BasicAuth`, `ClientCredentials` (a bearer token from `/auth/token`,
kept until shortly before it expires; pass a `TokenStore` to keep it
between requests) or `BearerToken`.

## Customers

```php
$login = $avaro->auth()->customers()->login($email, $password);
$customer = $avaro->withCustomer($login->token);

$product = $customer->shop()->products()->get('echinocactus-grusonii-123');
echo $product->price?->unit->net; // "12.4500"

$orders = $customer->orders()->list();
$pdf = $customer->invoices()->pdf($orders->items[0]->id);
```

`withCustomer()` returns a new client; calls on behalf of a contact
send its token as `X-Customer-Token`. Sign out with
`$customer->auth()->customers()->logout()`.

## Quotes and orders

```php
use Avaro\Sdk\Exception\TotalChanged;
use Avaro\Sdk\Shop\Consent;
use Avaro\Sdk\Shop\Line;
use Avaro\Sdk\Shop\OrderRequest;
use Avaro\Sdk\Shop\QuoteRequest;

$lines = [new Line($product->id, 12)];
$quote = $customer->shop()->quotes()->create(new QuoteRequest($lines));

if ($quote->valid) {
    $consents = array_map(Consent::to(...), $channel->legal);

    try {
        $placed = $customer->shop()->orders()->place(
            new OrderRequest($lines, $quote->shippingMethod, 'Invoice', $consents, $quote->total->gross),
            $checkoutId, // the same key again returns the same order
        );
    } catch (TotalChanged $changed) {
        // Show $changed->quote and ask again.
    }
}
```

Avaro calculates every price, tax and total; send the gross total the
customer saw as `expectedGross`, and nothing is ordered if it changed.

## Catalog sync

```php
$since = $store->position(); // null for the first sync
$cursor = null;

do {
    $page = $avaro->shop()->changes($since, $cursor);

    foreach ($page->changes as $change) {
        // $change->type, $change->id, $change->deleted
    }

    $cursor = $page->next;
} while (null !== $cursor);

$store->savePosition($page->until);
```

A position that is too old throws `Exception\Gone`; sync again without
`since` then.

## Errors

Every error is an `Exception\AvaroException`. Answers of the API are
`ApiException`s with the problem (`$error->problem`, `$error->slug()`):

| Status | Exception |
| --- | --- |
| 400 | `BadRequest` |
| 401 | `Unauthorized` |
| 403 | `Forbidden` |
| 404 | `NotFound` |
| 409 | `Conflict`, `TotalChanged`, `QuoteInvalid`, `IdempotencyConflict` (with `quote`) |
| 410 | `Gone` |
| 422 | `ValidationFailed` (`errors`, `messages()`) |
| 429 | `TooManyRequests` (`retryAfter`) |
| 5xx | `ServerError` (`problem->diagnosticId`) |

A failed connection is `TransportFailed`, a response the SDK cannot
read `UnexpectedResponse`.

## Webhooks

```php
use Avaro\Sdk\Webhooks\Webhook;

$event = (new Webhook($webhookSecret))->verify(getallheaders(), file_get_contents('php://input'));
// $event->type, e.g. "product.updated", $event->data['id']
```

## Development

```bash
composer install
composer check
```

The contract test compares the SDK with the OpenAPI files of API v3 when
`AVARO_API_V3_DIR` points to them.

## License

MIT, see [LICENSE](LICENSE).
