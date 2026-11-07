# Changelog

All notable changes, after [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versions follow [Semantic Versioning](https://semver.org/); one major
version per version of the API.

## [Unreleased]

### Added

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
