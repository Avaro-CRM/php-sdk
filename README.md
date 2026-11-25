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

## Holiday apartments and hotels

A client with `hotel.catalog:read` and `hotel.requests:write` shows the
units of one property with their rates, a calendar, quotes and sends
booking requests. The tenant confirms or declines them in Avaro.

```php
use Avaro\Sdk\Hotel\BookingRequest;
use Avaro\Sdk\Hotel\Consent;
use Avaro\Sdk\Hotel\Guest;
use Avaro\Sdk\Hotel\Guests;
use Avaro\Sdk\Hotel\RequiredDetail;
use Avaro\Sdk\Hotel\Stay;

$hotel = $avaro->hotel();
$channel = $hotel->channel();                    // property, legal texts, required details
$askPhone = $channel->requires(RequiredDetail::Phone);
$unit = $hotel->units()->get($unitId);           // rates, pets, fees
$days = $hotel->units()->calendar($unitId, $from, $to)->days;
$stay = new Stay($arrival, $departure, new Guests(adults: 2, children: 1, pets: 1), $unitId);
$quote = $hotel->quotes()->create($stay);        // lines, totals, problems
$free = $hotel->availability(new Stay($arrival, $departure, new Guests(2)));

try {
    $sent = $hotel->requests()->send(
        new BookingRequest($stay, new Guest('Janssen', 'greta@example.com'), $quote->totals->gross, [Consent::to($privacy)]),
        $idempotencyKey,
    );
} catch (\Avaro\Sdk\Exception\TotalChanged | \Avaro\Sdk\Exception\QuoteInvalid $error) {
    $quote = $error->stayQuote; // show it again
}
```

## Courses and events

A client with `events.catalog:read` and `events.bookings:write` shows
courses (categories with a price per attendee and attendee limits),
their upcoming events with free seats and books them for one or more
attendees. Avaro stores the booking and sends the confirmation and, as
set for the tenant, the invoice.

```php
use Avaro\Sdk\Events\Attendee;
use Avaro\Sdk\Events\Booking;
use Avaro\Sdk\Events\Consent;
use Avaro\Sdk\Events\Customer;
use Avaro\Sdk\Events\RequiredDetail;

$events = $avaro->events();
$channel = $events->channel();                   // legal texts, required details, maxAttendees
$course = $events->categories()->get($courseId); // price, attendantsMin, attendantsMax
$dates = $events->events()->list(category: $courseId, available: true);
$event = $events->events()->get($eventId);       // seats, booking status, fields

if ($event->bookable(2)) {
    try {
        $sent = $events->bookings()->book(Booking::of(
            $event,
            new Customer('Weber', 'anna@example.com', 'Anna', street: 'Kreuzweg', houseNumber: '3', postalCode: '52062', city: 'Aachen'),
            [new Attendee('Jonas', 'Weber', $birthday), new Attendee('Mia', 'Weber')],
            ['costs-by-company' => true],          // by the key of $event->fields
            [Consent::to($channel->legal[0])],
        ), $idempotencyKey);
    } catch (\Avaro\Sdk\Exception\SoldOut | \Avaro\Sdk\Exception\NotBookable | \Avaro\Sdk\Exception\TotalChanged $error) {
        $event = $error->event; // show it again
    }
}
```

`Booking::of()` sends the total Avaro checks: the gross price per
attendee times the attendees. A status or field type the SDK does not
know never counts as bookable.

### Fields for each attendee

`$event->participantFields` contains the separately configured questions for every
person (for example their driving school), including `key`, `type`, `required`,
`options`, `placeholder`, `hint` and `defaultValue`. Render them once per attendee
and pass answers by field key:

```php
$person = new Attendee('Anna', 'Weber', additionalFields: [
    '31' => 'Driving school A',
    '32' => true,
]);
```

Each person's answers are sent as `attendees[].additionalFields`. Types include
Text, Textarea, Number, Date (`YYYY-MM-DD`), Checkbox, Select, Email and Phone.
Existing booking-level `$event->fields` stay separate. Required answers and
allowed options are validated by Avaro for every attendee before completing the
booking. Website forms must implement this rendering before required participant
fields are enabled.

## Users, companies and todos

A server client that speaks for another system (its external system is
set once in Avaro, e.g. `ExamplePlatform`) creates the users and
companies it needs and then acts for one of the users. It needs the scopes
`company.users:write`, `company.addresses:write` (and an Avaro user of the
client), `auth.users:exchange` and `todos.todos:read`/`todos.todos:write`.

```php
use Avaro\Sdk\Company\{AddressData, UserProfile};
use Avaro\Sdk\Todos\{AddressReference, Changes, NewTodo, TodoList};

$avaro = Avaro::create($baseUrl, new ClientCredentials($id, $secret), $http, $factory, $factory);

// Once when a person is added on the platform; repeating it changes nothing.
try {
    $avaro->company()->users()->provision('ExamplePlatform', $userId, new UserProfile($email, $firstName, $lastName));
} catch (\Avaro\Sdk\Exception\UserLinkRequested) {
    // An employee of the tenant has this email: a tenant admin confirms the link in Avaro, then repeat the call.
} catch (\Avaro\Sdk\Exception\UserLinkRejected) {
    // The tenant rejected linking this person.
}

// On registration and every change of a company; links the one address with the customer number, else creates it.
$avaro->company()->addresses()->provision('ExamplePlatform', $companyId, AddressData::named($name)
    ->customerNumber($customerNumber)
    ->location($street, $streetNumber, $zipcode, $city, 'DE')
    ->email($email)
    ->phone($phone)
    ->website($website));

$todos = $avaro->asUser($userId)->todos()->todos();
$summary = $todos->summary();                       // open, dueToday, overdue, highPriority
$page = $todos->list(assignee: TodoList::ME, dueTo: date('Y-m-d'));
$company = new AddressReference('ExamplePlatform', $companyId, $customerNumber); // linked on first write
$created = $todos->create(new NewTodo('Kündigung besprechen', address: $company, sourceKey: 'SubscriptionTerminated:' . $subscriptionId), $idempotencyKey);
$todo = $todos->get($created->todo->id);
$todos->update($todo->id, Changes::new()->complete(), $todo->etag);
```

A `sourceKey` is unique while its todo is open: `create()` then returns
that todo with `$created->created === false`.

### CRM activities

With `crm.activities:read`/`crm.activities:write` the client reads the
history of a company and records notes and calls as the user. The user
needs the right to read the address in Avaro.

```php
use Avaro\Sdk\Crm\{Activities, Call, NewActivity};

$activities = $avaro->asUser($userId)->crm()->activities();
$page = $activities->list($company, [Activities::NOTE, Activities::PHONE_CALL], limit: 20); // newest first
foreach ($page->items as $activity) {
    echo $activity->occurredAt->format('d.m.Y H:i'), ' ', $activity->title, ' ', $activity->author?->name, "\n";
}
$activities->create(NewActivity::note($company, 'Möchte zwei Standorte anbinden.', 'Note:' . $noteId));
$activities->create(NewActivity::phoneCall($company, 'Rückruf vereinbart.', 'Outgoing', 125, Call::COMPLETED));
```

## Errors

Every error is an `Exception\AvaroException`. Answers of the API are
`ApiException`s with the problem (`$error->problem`, `$error->slug()`):

| Status | Exception |
| --- | --- |
| 400 | `BadRequest` |
| 401 | `Unauthorized` |
| 403 | `Forbidden` |
| 404 | `NotFound` |
| 409 | `Conflict`, `TotalChanged`, `QuoteInvalid`, `SoldOut`, `NotBookable`, `IdempotencyConflict` (with `quote` of the shop, `stayQuote` of a stay or `event` of a booking), `UserLinkRequested`, `UserLinkRejected` |
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

Development takes place in the Avaro monorepo under
`packages/php/avaro-sdk/`. `Avaro-CRM/php-sdk` is the one-way distribution
mirror; changes belong in the monorepo. SDK releases have their own versions.

```bash
composer install
composer check
```

The contract test compares the SDK with the OpenAPI files of API v3 when
`AVARO_API_V3_DIR` points to them. From the package directory in the monorepo:

```bash
AVARO_API_V3_DIR="../../../docs/api/v3" composer check
```

## License

MIT, see [LICENSE](LICENSE).
