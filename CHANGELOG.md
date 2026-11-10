# Changelog

All notable changes, after [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versions follow [Semantic Versioning](https://semver.org/); one major
version per version of the API.

## [Unreleased]

### Added

- `events()`: channel with legal texts and required details, categories
  with price and attendee limits, upcoming events with free seats,
  booking status and fields, and bookings for one or more attendees with
  idempotency key; `SoldOut` and `NotBookable`, and `event` on conflicts
  of a booking.
- `hotel()`: channel with legal texts and required details, units with rates, pets and fees, calendar per
  unit, availability, quotes and booking requests with idempotency key;
  `stayQuote` on conflicts of a request.
- `tags` of a product and a product summary, e.g. `startseite`.

- Client `Avaro` for API v3 over PSR-18 and PSR-17 with `ApiKey`,
  `BasicAuth`, `ClientCredentials` (token kept until shortly before it
  expires, optional `TokenStore`) and `BearerToken`.
- `auth()->customers()`: sign-in, sign-out, profile, password and
  password reset of contacts; `withCustomer($token)` for their calls.
- `Customer::$personalApproach`: how the contact wants to be addressed,
  `ByThem` (formal) or `ByYou` (informal).
- `shop()`: channel, categories, products with prices for a customer,
  catalog changes for a sync, quotes and orders with idempotency key.
- `orders()` and `invoices()` of the signed-in customer, invoices as PDF.
- Errors as exceptions (`ValidationFailed` with the errors per field,
  `TotalChanged` and `QuoteInvalid` with the quote, `TooManyRequests`
  with `retryAfter` …), `Webhooks\Webhook` checks Standard Webhooks.
