# Changelog

All notable changes, after [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versions follow [Semantic Versioning](https://semver.org/); one major
version per version of the API.

## [Unreleased]

## [1.4.0] - 2026-11-28

### Added

- `company()->addresses()->provisionContact($system, $addressExternalId, $contactExternalId, ContactData)`
  links or creates a contact person of a company by its ID in the
  client's system: a known ID is answered as it is, `ContactData::link($contactId)`
  links an existing contact person without changing it,
  `ContactData::named($firstName, $lastName)` with `title()`, `email()`,
  `phone()` and `mobile()` creates one. Conflicts carry the slugs
  `contact-missing`, `contact-linked-elsewhere`, `address-deleted`,
  `address-missing` and `busy`.
- `Company\AddressContact::$externalId`: the contact's ID in the client's
  own system, null without one (also in `get(..., contacts: true)`).

## [1.3.0] - 2026-11-26

### Added

- `company()->addresses()->get($system, $externalId, contacts: true)` reads
  the contact persons of a company from the CRM (`expand=contacts`, can be
  combined with `additionalFields: true`). Only active contacts outside
  the trash are listed.
- `Company\Address::$contacts` (`list<AddressContact>`, null when not
  requested); new classes `Company\AddressContact` (`id`, `title`,
  `firstName`, `lastName`, `position`, `fields`, with `name()`, `email()`,
  `phone()` and `fieldsOfType()`) and `Company\AddressContactField`
  (`id`, `type`, `scope`, `value`, `primary`).

## [1.2.0] - 2026-11-26

### Added

- `company()->addresses()->update($system, $externalId, AddressChanges, additionalFields: bool)`
  changes the target account and additional fields of a company for a
  user (`asUser()`); only what is set changes. `AddressChanges` has
  `targetAccount()`, `value()`, `clear()` and `tags($fieldId, existing:, new:)`.
- `company()->addresses()->fields()` with `Company\AddressFieldDefinition`
  (type, mandatory, options of a selection) and
  `company()->addresses()->fieldTags($fieldId, $search, $limit)` for tag
  suggestions.

### Changed

- `setTargetAccount()` sends through `update()`; the request is the same.

## [1.1.0] - 2026-11-26

### Added

- `UserLinkRequested` (`user-link-requested`) and `UserLinkRejected`
  (`user-link-rejected`) from `company()->users()->provision()`: an
  employee of the tenant already has the email, so Avaro records a link
  request the tenant approves or rejects instead of creating a user.
- `Company\Address::$targetAccount` (`?bool`) and
  `Company\Address::$additionalFields` (`list<AdditionalField>`, null when
  not requested) with `additionalField($title)`; new classes
  `Company\AdditionalField` and `Company\AdditionalFieldTag`.
- `company()->addresses()->get($system, $externalId, additionalFields: true)`
  reads the additional fields, e.g. KAM-Fit.
- `company()->addresses()->setTargetAccount($system, $externalId, ?bool)`
  sets the target account for a user (`asUser()`), through
  `PATCH /company/addresses/by-external-id/{system}/{externalId}`.
- `Shop\Channel::$pricesPublic`: guests without a customer token get
  prices from `shop()->products()`, the price of the online shop for
  everyone. `priceDisplay` now follows the settings of the client.

### Changed

- `company()->users()->provision()`: first and last name may be empty
  strings; only the email is required.

## [1.0.0] - 2026-11-24

### Added

- `crm()->activities()`: the CRM activities of an address with `list()`,
  `each()` and `get()` (`Activity`, `Call`, `Participant`), and `create()`
  for notes, calls, visits and emails (`NewActivity::note()`,
  `NewActivity::phoneCall()`, `CreatedActivity::$created`).
- `company()->addresses()`: read addresses (companies) by their ID in the
  client's external system and provision them (`AddressData`: only the
  fields set are sent, null clears; `Address::$created`).
- `company()->users()`: read users by their ID in the client's external
  system and provision them (`provision()` creates once, keeps the
  profile; `User::$created`).
- `asUser($externalUserId)` and `ClientCredentials::forUser()`: user
  tokens by token exchange (`UserCredentials`, kept until shortly before
  they expire).
- `todos()`: list with filters and `each()`, counters, assignees, read
  with ETag, create with idempotency key and `sourceKey` (`CreatedTodo`),
  change as Merge Patch (`Changes`) with `If-Match`, delete to the trash;
  `PreconditionFailed` for 412; `AddressReference` with the customer
  number as fallback while the company ID is not linked yet.
- `events()`: channel with legal texts and required details, categories
  with price and attendee limits, upcoming events with free seats,
  booking status and fields, and bookings for one or more attendees with
  idempotency key; `SoldOut` and `NotBookable`, and `event` on conflicts
  of a booking.
- `hotel()`: channel with legal texts and required details, units with rates, pets and fees, calendar per
  unit, availability, quotes and booking requests with idempotency key;
  `stayQuote` on conflicts of a request; `Quote::$priceOnRequest` when a
  night has no seasonal price (no lines, totals 0, still requestable).
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
