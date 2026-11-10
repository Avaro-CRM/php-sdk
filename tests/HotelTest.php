<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Exception\IdempotencyConflict;
use Avaro\Sdk\Exception\NotFound;
use Avaro\Sdk\Exception\QuoteInvalid;
use Avaro\Sdk\Exception\TotalChanged;
use Avaro\Sdk\Exception\ValidationFailed;
use Avaro\Sdk\Hotel\BookingRequest;
use Avaro\Sdk\Hotel\Consent;
use Avaro\Sdk\Hotel\DayStatus;
use Avaro\Sdk\Hotel\Guest;
use Avaro\Sdk\Hotel\Guests;
use Avaro\Sdk\Hotel\LineType;
use Avaro\Sdk\Hotel\RequiredDetail;
use Avaro\Sdk\Hotel\Salutation;
use Avaro\Sdk\Hotel\Stay;
use Avaro\Sdk\Tests\Support\Fixtures;
use DateTimeImmutable;
use InvalidArgumentException;

final class HotelTest extends TestCase
{
    public function testTheChannel(): void
    {
        $this->http->fixture('hotel-channel');

        $channel = $this->avaro()->hotel()->channel();

        self::assertSame('https://app.avaro.test/rest/v3/hotel/channel', (string) $this->http->last()->getUri());
        self::assertSame('Haus Dohlennest', $channel->property->title);
        self::assertSame('Privacy', $channel->legal[0]->key);
        self::assertTrue($channel->legal[0]->required);
        self::assertSame([RequiredDetail::Address, RequiredDetail::Phone], $channel->requiredDetails, 'Unknown details are left out.');
        self::assertTrue($channel->requires(RequiredDetail::Phone));
        self::assertFalse($channel->requires(RequiredDetail::FirstName));
    }

    public function testAChannelOfAnOlderAvaroRequiresNothingMore(): void
    {
        $data = Fixtures::load('hotel-channel');
        unset($data['requiredDetails']);
        $this->http->answer(200, $data);

        self::assertSame([], $this->avaro()->hotel()->channel()->requiredDetails);
    }

    public function testUnitsWithRatesPetsAndFees(): void
    {
        $last = Fixtures::load('hotel-units');
        $last['next'] = null;
        $this->http->fixture('hotel-units')->answer(200, $last);

        $units = iterator_to_array($this->avaro()->hotel()->units()->each(limit: 1), false);

        self::assertCount(2, $units);
        self::assertSame('https://app.avaro.test/rest/v3/hotel/units?limit=1&cursor=cursor-2', (string) $this->http->last()->getUri());
        $unit = $units[0];
        self::assertSame([4, 1, 1], [$unit->maxGuests, $unit->maxInfants, $unit->maxPets]);
        self::assertNull($unit->rates[0]->season);
        self::assertSame('Hauptsaison', $unit->rates[1]->season?->title);
        self::assertSame('2027-08-31', $unit->rates[1]->season->to->format('Y-m-d'));
        self::assertSame('20.0000', $unit->rates[1]->extraGuestPrice?->gross);
        self::assertSame('Hund auf Anfrage', $unit->pets?->note);
        self::assertSame(['Bettwäsche', 'Handtücher', 'Endreinigung'], $unit->fees[0]->includes);

        self::assertSame(3, $unit->rateOn(new DateTimeImmutable('2027-07-01'))?->minNights);
        self::assertNull($unit->rateOn(new DateTimeImmutable('2027-09-01'))?->season, 'Outside every season the default rate.');
    }

    public function testOneUnitAndItsCalendar(): void
    {
        $this->http->fixture('hotel-unit')->fixture('hotel-calendar')->answer(404, ['type' => 'https://app.avaro.test/rest/v3/problems/not-found', 'title' => 'Not found', 'status' => 404]);
        $units = $this->avaro()->hotel()->units();

        $unit = $units->get('01JD0UNIT00000000000000DB');
        self::assertSame(['Wohnung Dünenblick', '12'], [$unit->title, $unit->code]);

        $calendar = $units->calendar('01JD0UNIT00000000000000DB', new DateTimeImmutable('2027-07-09'), new DateTimeImmutable('2027-07-15'));
        self::assertSame('https://app.avaro.test/rest/v3/hotel/units/01JD0UNIT00000000000000DB/calendar?from=2027-07-09&to=2027-07-15', (string) $this->http->last()->getUri());
        self::assertSame(DayStatus::DepartureOnly, $calendar->day('2027-07-10')?->status);
        self::assertTrue($calendar->day('2027-07-14')?->arrival);
        self::assertSame(DayStatus::Booked, $calendar->day('2027-07-15')?->status, 'A status the SDK does not know counts as booked.');
        self::assertNull($calendar->day('2027-07-20'));

        $this->expectException(NotFound::class);
        $units->get('unknown');
    }

    public function testQuoteAndAvailability(): void
    {
        $this->http->fixture('hotel-quote')->answer(200, ['data' => [Fixtures::load('hotel-quote')]]);
        $stay = new Stay(new DateTimeImmutable('2027-07-01 15:00'), new DateTimeImmutable('2027-07-04'), new Guests(2, 2, pets: 1), '01JD0UNIT00000000000000DB');

        $quote = $this->avaro()->hotel()->quotes()->create($stay);
        self::assertSame(['unit' => '01JD0UNIT00000000000000DB', 'arrival' => '2027-07-01', 'departure' => '2027-07-04', 'guests' => ['adults' => 2, 'children' => 2, 'infants' => 0, 'pets' => 1]], $this->http->lastJson());
        self::assertSame(3, $stay->nights());
        self::assertSame([LineType::Stay, LineType::ExtraGuests, LineType::Pets, LineType::Fee], array_map(static fn($line) => $line->type, $quote->lines));
        self::assertSame('Hauptsaison', $quote->lines[0]->season?->title);
        self::assertSame(84000, $quote->totals->gross);
        self::assertTrue($quote->valid);
        self::assertFalse($quote->hasProblem('MinNights'));

        $free = $this->avaro()->hotel()->availability(new Stay(new DateTimeImmutable('2027-07-01'), new DateTimeImmutable('2027-07-04'), new Guests(2)));
        self::assertSame('https://app.avaro.test/rest/v3/hotel/availability?arrival=2027-07-01&departure=2027-07-04&adults=2&children=0&infants=0&pets=0', (string) $this->http->last()->getUri());
        self::assertSame('Wohnung Dünenblick', $free[0]->unit->title);
    }

    public function testAStayChecksItsDates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Stay(new DateTimeImmutable('2027-07-04'), new DateTimeImmutable('2027-07-04'), new Guests(2));
    }

    public function testARequestNeedsAKeyAndSendsTheGuest(): void
    {
        $this->http->fixture('hotel-request', 201)->fixture('hotel-request');
        $request = $this->request();

        $sent = $this->avaro()->hotel()->requests()->send($request, 'dohlennest-0001');
        self::assertSame('dohlennest-0001', $this->http->last()->getHeaderLine('Idempotency-Key'));
        $body = $this->http->lastJson();
        self::assertSame(['gross' => 84000], $body['expectedTotal']);
        self::assertSame([['key' => 'Privacy', 'version' => '2027-01']], $body['consents']);
        self::assertSame(['salutation' => 'Ms', 'firstName' => 'Greta', 'lastName' => 'Janssen', 'street' => 'Strandstraße', 'houseNumber' => '12a',
            'addition' => null, 'postalCode' => '26757', 'city' => 'Borkum', 'country' => 'DE', 'email' => 'greta@example.test', 'phone' => null], $body['guest']);
        self::assertSame('B-2027-0042', $sent->request->number);
        self::assertSame('Requested', $sent->request->status);
        self::assertFalse($sent->repeated);
        self::assertTrue($this->avaro()->hotel()->requests()->send($request, 'dohlennest-0001')->repeated);

        $this->expectException(InvalidArgumentException::class);
        $this->avaro()->hotel()->requests()->send($request, 'short');
    }

    public function testConflictsCarryTheQuoteOfTheStay(): void
    {
        $quote = Fixtures::load('hotel-quote');
        $taken = ['valid' => false, 'problems' => [['code' => 'Unavailable', 'detail' => 'Belegt.', 'limit' => null]]] + $quote;
        $this->http
            ->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/total-changed', 'title' => 'The total changed.', 'status' => 409, 'quote' => $quote])
            ->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/quote-invalid', 'title' => 'The stay cannot be requested.', 'status' => 409, 'quote' => $taken])
            ->answer(409, ['type' => 'https://app.avaro.test/rest/v3/problems/idempotency-conflict', 'title' => 'Conflict', 'status' => 409])
            ->answer(422, ['type' => 'https://app.avaro.test/rest/v3/problems/validation-failed', 'title' => 'Invalid', 'status' => 422, 'errors' => [['pointer' => '/consents', 'detail' => 'Required.', 'code' => 'ConsentMissing']]]);
        $requests = $this->avaro()->hotel()->requests();

        try {
            $requests->send($this->request(), 'dohlennest-0002');
            self::fail('No conflict.');
        } catch (TotalChanged $error) {
            self::assertSame(84000, $error->stayQuote?->totals->gross);
            self::assertNull($error->quote);
        }

        try {
            $requests->send($this->request(), 'dohlennest-0003');
            self::fail('No conflict.');
        } catch (QuoteInvalid $error) {
            self::assertTrue($error->stayQuote?->hasProblem('Unavailable'));
        }

        try {
            $requests->send($this->request(), 'dohlennest-0004');
            self::fail('No conflict.');
        } catch (IdempotencyConflict $error) {
            self::assertNull($error->stayQuote);
        }

        try {
            $requests->send($this->request(), 'dohlennest-0005');
            self::fail('No error.');
        } catch (ValidationFailed $error) {
            self::assertSame('ConsentMissing', $error->errors[0]->code);
        }
    }

    private function request(): BookingRequest
    {
        return new BookingRequest(
            new Stay(new DateTimeImmutable('2027-07-01'), new DateTimeImmutable('2027-07-04'), new Guests(2, 2, pets: 1), '01JD0UNIT00000000000000DB'),
            new Guest('Janssen', 'greta@example.test', 'Greta', Salutation::Ms, 'Strandstraße', '12a', postalCode: '26757', city: 'Borkum', country: 'DE'),
            84000,
            [new Consent('Privacy', '2027-01')],
        );
    }
}
