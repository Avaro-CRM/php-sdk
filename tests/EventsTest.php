<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Events\Attendee;
use Avaro\Sdk\Events\Booking;
use Avaro\Sdk\Events\BookingStatus;
use Avaro\Sdk\Events\Consent;
use Avaro\Sdk\Events\Customer;
use Avaro\Sdk\Events\Event;
use Avaro\Sdk\Events\FieldType;
use Avaro\Sdk\Events\RequiredDetail;
use Avaro\Sdk\Events\Salutation;
use Avaro\Sdk\Exception\IdempotencyConflict;
use Avaro\Sdk\Exception\NotBookable;
use Avaro\Sdk\Exception\NotFound;
use Avaro\Sdk\Exception\SoldOut;
use Avaro\Sdk\Exception\TotalChanged;
use Avaro\Sdk\Exception\ValidationFailed;
use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Tests\Support\Fixtures;
use DateTimeImmutable;
use InvalidArgumentException;

final class EventsTest extends TestCase
{
    public function testTheChannel(): void
    {
        $this->http->fixture('events-channel');

        $channel = $this->avaro()->events()->channel();

        self::assertSame('https://app.avaro.test/rest/v3/events/channel', (string) $this->http->last()->getUri());
        self::assertSame([RequiredDetail::Address, RequiredDetail::DateOfBirth], $channel->requiredDetails, 'A detail the SDK does not know is left out.');
        self::assertTrue($channel->requires(RequiredDetail::DateOfBirth));
        self::assertFalse($channel->requires(RequiredDetail::Phone));
        self::assertSame(3, $channel->maxAttendees);
        self::assertSame('https://erste-hilfe.example/agb', $channel->legal[0]->url);
        self::assertNull($channel->legal[1]->url);
        self::assertSame('Abmeldung bis 48 Stunden vorher, sonst volle Gebühr.', $channel->legal[1]->text);
    }

    public function testTheAttendeesOfEvents(): void
    {
        $one = Fixtures::load('events-event-attendees');
        $this->http->answer(200, ['data' => [$one]])->answer(200, ['data' => []]);

        $lists = $this->avaro()->events()->attendees(['1410', ...array_map('strval', range(1, 50))]);

        self::assertSame('https://app.avaro.test/rest/v3/events/attendees?events=50', urldecode((string) $this->http->last()->getUri()), 'More than 50 events take a second call.');
        self::assertCount(1, $lists);
        self::assertSame('1410', $lists[0]->event->id);
        self::assertSame(['Kim', 'Weber'], [$lists[0]->attendees[0]->firstName, $lists[0]->attendees[0]->lastName]);
        self::assertSame('2026-11-02T13:30:00+00:00', $lists[0]->attendees[0]->bookedAt?->format(DATE_ATOM));
        self::assertNull($lists[0]->attendees[1]->bookedAt);

        $this->expectException(InvalidArgumentException::class);
        $this->avaro()->events()->attendees([]);
    }

    public function testCategoriesWithTheirDefaults(): void
    {
        $last = Fixtures::load('events-categories');
        $last['next'] = null;
        $this->http->fixture('events-categories')->answer(200, $last)->fixture('events-category');
        $categories = $this->avaro()->events()->categories();

        $all = iterator_to_array($categories->each(limit: 2), false);
        self::assertCount(4, $all);
        self::assertSame('https://app.avaro.test/rest/v3/events/categories?limit=2&cursor=cursor-2', (string) $this->http->last()->getUri());
        self::assertSame(['40.0000', 4, 20, null], [$all[0]->price?->gross, $all[0]->attendantsMin, $all[0]->attendantsMax, $all[0]->parent]);
        self::assertSame(['2', null, null], [$all[1]->parent, $all[1]->price, $all[1]->attendantsMin]);

        self::assertSame('Grundkurs', $categories->get('2')->subtitle);
        self::assertSame('https://app.avaro.test/rest/v3/events/categories/2', (string) $this->http->last()->getUri());
    }

    public function testEventsWithSeatsStatusAndFields(): void
    {
        $this->http->fixture('events-events')->fixture('events-event')->answer(404, ['type' => 'https://app.avaro.test/rest/v3/problems/not-found', 'title' => 'Not found', 'status' => 404]);
        $events = $this->avaro()->events()->events();

        $page = $events->list(category: '2', from: new DateTimeImmutable('2027-06-01T00:00:00+02:00'), available: true, limit: 20);
        self::assertSame('https://app.avaro.test/rest/v3/events/events?category=2&from=2027-06-01T00%3A00%3A00%2B02%3A00&available=true&limit=20', (string) $this->http->last()->getUri());
        self::assertSame('cursor-2', $page->next);
        [$open, $full] = $page->items;

        self::assertSame(['K-10', 'Erste Hilfe', 'Schulungsraum Aachen', 'Theaterstraße 1'], [$open->number, $open->category?->title, $open->venue?->title, $open->venue?->street]);
        self::assertSame('2027-06-01 07:00', $open->startsAt->format('Y-m-d H:i'));
        self::assertSame([20, 18, 2], [$open->seats->max, $open->seats->booked, $open->seats->free]);
        self::assertSame(BookingStatus::Open, $open->booking->status);
        self::assertSame('2027-05-31', $open->booking->to?->format('Y-m-d'));
        self::assertSame(FieldType::Checkbox, $open->field('ich-habe-die-agb-gelesen')?->type);
        self::assertTrue($open->field('ich-habe-die-agb-gelesen')->required);
        self::assertNull($open->field('unknown'));
        self::assertTrue($open->bookable(2));
        self::assertFalse($open->bookable(3), 'Two seats are free.');
        self::assertSame(8000, $open->totalGross(2));

        self::assertSame(BookingStatus::SoldOut, $full->booking->status);
        self::assertFalse($full->bookable());
        self::assertSame(0, $full->totalGross(2), 'Free of charge.');
        self::assertNull($full->venue);

        self::assertSame('10', $events->get('10')->id);
        $this->expectException(NotFound::class);
        $events->get('99');
    }

    public function testUnknownValuesNeverMakeAnEventBookable(): void
    {
        self::assertSame(BookingStatus::Offline, self::event(['booking' => ['status' => 'Waitlist']])->booking->status);

        $event = self::event(['fields' => [2 => ['key' => 'shirt', 'title' => 'T-Shirt-Größe', 'type' => 'Size', 'required' => true, 'options' => []]]]);
        self::assertNull($event->field('shirt')?->type);
        self::assertFalse($event->bookable(), 'A required field of an unknown type cannot be filled in.');
    }

    public function testTotalsInCentsWithoutFloats(): void
    {
        foreach (['39.9900' => 3999, '0.0050' => 1, '12.3449' => 1234, '100' => 10000] as $gross => $cents) {
            self::assertSame($cents * 3, self::event(['price' => ['gross' => (string) $gross]])->totalGross(3), (string) $gross);
        }
    }

    public function testABookingNeedsAKeyAndSendsCustomerAndAttendees(): void
    {
        $this->http->fixture('events-event')->fixture('events-booking', 201)->fixture('events-booking');
        $avaro = $this->avaro();
        $event = $avaro->events()->events()->get('10');
        $booking = $this->booking($event);

        $sent = $avaro->events()->bookings()->book($booking, 'ehc-booking-0001');
        self::assertSame('https://app.avaro.test/rest/v3/events/bookings', (string) $this->http->last()->getUri());
        self::assertSame('ehc-booking-0001', $this->http->last()->getHeaderLine('Idempotency-Key'));
        $body = $this->http->lastJson();
        self::assertSame('10', $body['event']);
        self::assertSame(['gross' => 8000], $body['expectedTotal']);
        self::assertSame([['firstName' => 'Jonas', 'lastName' => 'Weber', 'dateOfBirth' => '1985-02-14'], ['firstName' => 'Mia', 'lastName' => 'Weber', 'dateOfBirth' => null]], $body['attendees']);
        self::assertSame(['ich-habe-die-agb-gelesen' => true], $body['fields']);
        self::assertSame([['key' => 'Terms', 'version' => '2027-01']], $body['consents']);
        self::assertSame(['salutation' => 'Ms', 'company' => 'Weber GmbH', 'firstName' => 'Anna', 'lastName' => 'Weber', 'street' => 'Kreuzweg', 'houseNumber' => '3',
            'addition' => null, 'postalCode' => '52062', 'city' => 'Aachen', 'country' => 'DE', 'email' => 'anna@example.test', 'phone' => null], $body['customer']);
        self::assertSame(['B-2027-0042', 'Booked', 2, 8000, '10'], [$sent->booking->number, $sent->booking->status, $sent->booking->attendees, $sent->booking->totals->gross, $sent->booking->event->id]);
        self::assertFalse($sent->repeated);
        self::assertTrue($avaro->events()->bookings()->book($booking, 'ehc-booking-0001')->repeated);

        $this->expectException(InvalidArgumentException::class);
        $avaro->events()->bookings()->book($booking, 'short');
    }

    public function testFieldsGoAsAnObjectEvenWhenEmpty(): void
    {
        $booking = new Booking('10', new Customer('Weber', 'anna@example.test'), [new Attendee('Anna', 'Weber')], 4000);

        self::assertSame('{}', json_encode($booking->toArray()['fields']));
    }

    public function testABookingNeedsAttendees(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Booking('10', new Customer('Weber', 'anna@example.test'), [], 0);
    }

    public function testConflictsCarryTheEventAsItIsNow(): void
    {
        $event = Fixtures::load('events-event');
        $full = ['seats' => ['max' => 20, 'booked' => 20, 'free' => 0], 'booking' => ['status' => 'SoldOut', 'from' => null, 'to' => null]] + $event;
        $closed = ['booking' => ['status' => 'Closed', 'from' => null, 'to' => null]] + $event;
        $dearer = ['price' => ['net' => '37.8151', 'gross' => '45.0000', 'taxRate' => '19.00']] + $event;
        $problem = static fn(string $slug, array $more = []): array => ['type' => 'https://app.avaro.test/rest/v3/problems/' . $slug, 'title' => 'Conflict', 'status' => 409] + $more;
        $this->http
            ->answer(409, $problem('sold-out', ['event' => $full]))
            ->answer(409, $problem('not-bookable', ['event' => $closed]))
            ->answer(409, $problem('total-changed', ['event' => $dearer]))
            ->answer(409, $problem('idempotency-conflict'))
            ->answer(422, ['type' => 'https://app.avaro.test/rest/v3/problems/validation-failed', 'title' => 'Invalid', 'status' => 422, 'errors' => [['pointer' => '/attendees', 'detail' => 'At most 3.', 'code' => 'TooManyAttendees']]]);
        $bookings = $this->avaro()->events()->bookings();
        $booking = $this->booking(self::event());

        try {
            $bookings->book($booking, 'ehc-conflict-0001');
            self::fail('No conflict.');
        } catch (SoldOut $error) {
            self::assertSame(0, $error->event?->seats->free);
        }

        try {
            $bookings->book($booking, 'ehc-conflict-0002');
            self::fail('No conflict.');
        } catch (NotBookable $error) {
            self::assertSame(BookingStatus::Closed, $error->event?->booking->status);
        }

        try {
            $bookings->book($booking, 'ehc-conflict-0003');
            self::fail('No conflict.');
        } catch (TotalChanged $error) {
            self::assertSame(9000, $error->event?->totalGross(2));
            self::assertNull($error->quote);
            self::assertNull($error->stayQuote);
        }

        try {
            $bookings->book($booking, 'ehc-conflict-0004');
            self::fail('No conflict.');
        } catch (IdempotencyConflict $error) {
            self::assertNull($error->event);
        }

        try {
            $bookings->book($booking, 'ehc-conflict-0005');
            self::fail('No error.');
        } catch (ValidationFailed $error) {
            self::assertSame('TooManyAttendees', $error->errors[0]->code);
        }
    }

    /**
     * The event of the fixture with changes merged in.
     *
     * @param array<string, mixed> $changes
     */
    private static function event(array $changes = []): Event
    {
        /** @var array<string, mixed> $data */
        $data = array_replace_recursive(Fixtures::load('events-event'), $changes);

        return Event::fromData(new Data($data));
    }

    private function booking(Event $event): Booking
    {
        return Booking::of(
            $event,
            new Customer('Weber', 'anna@example.test', 'Anna', Salutation::Ms, 'Weber GmbH', 'Kreuzweg', '3', postalCode: '52062', city: 'Aachen', country: 'DE'),
            [new Attendee('Jonas', 'Weber', new DateTimeImmutable('1985-02-14')), new Attendee('Mia', 'Weber')],
            ['ich-habe-die-agb-gelesen' => true],
            [new Consent('Terms', '2027-01')],
        );
    }
}
