<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Tests\Domain\Unsubscribe;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Domain\Unsubscribe\UnsubscribeValidator;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class UnsubscribeValidatorTest extends ContaoTestCase
{
    private const string TRANS_DOMAIN = 'mc_calendar_event_booking';

    public function testFailsWhenBookingIsNull(): void
    {
        $result = $this->validator()->validate(null, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.invalid_uuid', $result->message);
        $this->assertSame(SeverityLevel::ERROR->value, $result->severity);
        $this->assertSame('error booking-not-found', $result->cssClass);
    }

    public function testFailsWhenEventNotFound(): void
    {
        // The booking has no related event (getRelated('pid') returns null).
        $booking = $this->mockBooking(null);

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.event_not_found', $result->message);
        $this->assertSame(SeverityLevel::ERROR->value, $result->severity);
        $this->assertSame('error event-not-found', $result->cssClass);
    }

    public function testFailsWhenCalendarNotFound(): void
    {
        $booking = $this->mockBooking($this->mockEvent(null));

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.calendar_not_found', $result->message);
        $this->assertSame(SeverityLevel::ERROR->value, $result->severity);
        $this->assertSame('error calendar-not-found', $result->cssClass);
    }

    public function testFailsWhenEventBookingIsNotAllowedForCalendar(): void
    {
        $booking = $this->mockBookingChain(calendar: ['allowEventBooking' => false]);

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.booking_not_allowed_for_this_calendar', $result->message);
        $this->assertSame(SeverityLevel::ERROR->value, $result->severity);
        $this->assertSame('error booking-not-allowed-for-this-calednar', $result->cssClass);
    }

    public function testReturnsUnsubscribeSuccessWhenCanceledAndFlagIsSet(): void
    {
        // A cancelled booking that arrives with the "just unsubscribed" flag set is
        // treated as a success confirmation, not an error.
        $booking = $this->mockBookingChain(booking: ['canceled' => true]);

        $result = $this->validator()->validate($booking, true, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.info.unsubscribe_success', $result->message);
        $this->assertSame(SeverityLevel::INFO->value, $result->severity);
        $this->assertSame('info booking-already-canceled', $result->cssClass);
        $this->assertSame(['hasUnsubscribed' => true], $result->flags);
    }

    public function testReturnsAlreadyUnsubscribedWhenCanceledAndFlagIsNotSet(): void
    {
        $booking = $this->mockBookingChain(booking: ['canceled' => true]);

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.info.already_unsubscribed', $result->message);
        $this->assertSame(SeverityLevel::INFO->value, $result->severity);
        $this->assertSame('info booking-already-canceled', $result->cssClass);
        $this->assertSame(['hasUnsubscribed' => true], $result->flags);
    }

    public function testFailsWhenDeregistrationIsDisabled(): void
    {
        $booking = $this->mockBookingChain(event: ['enableDeregistration' => false]);

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.unsubscription_not_allowed', $result->message);
        $this->assertSame(SeverityLevel::ERROR->value, $result->severity);
        $this->assertSame('error unsubscription-not-allowed', $result->cssClass);
    }

    public function testFailsWhenUnsubscribeLimitHasExpired(): void
    {
        // An explicit unsubscribe deadline in the past makes the booking non-cancellable.
        $booking = $this->mockBookingChain(event: ['unsubscribeLimitTstamp' => time() - 3600]);

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.unsubscription_limit_expired', $result->message);
        $this->assertSame(SeverityLevel::ERROR->value, $result->severity);
        $this->assertSame('error unsubscription-limit-expired', $result->cssClass);
    }

    public function testReturnsOkForAValidUnsubscribableBooking(): void
    {
        $event = $this->mockEvent($this->mockCalendar());
        $booking = $this->mockBooking($event);

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isOk());
        $this->assertSame($event, $result->value);
    }

    private function validator(): UnsubscribeValidator
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => $id)
        ;

        return new UnsubscribeValidator($translator);
    }

    /**
     * Builds a full booking -> event -> calendar chain that passes every check by
     * default, so a single override array can drive the branch under test.
     *
     * @param array<string, mixed> $booking
     * @param array<string, mixed> $event
     * @param array<string, mixed> $calendar
     */
    private function mockBookingChain(array $booking = [], array $event = [], array $calendar = []): CalendarEventsMemberModel&MockObject
    {
        return $this->mockBooking($this->mockEvent($this->mockCalendar($calendar), $event), $booking);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function mockBooking(CalendarEventsModel|null $event, array $overrides = []): CalendarEventsMemberModel&MockObject
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, array_merge(
            ['id' => 5, 'canceled' => false],
            $overrides,
        ));

        $booking
            ->method('getRelated')
            ->with('pid')
            ->willReturn($event)
        ;

        return $booking;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function mockEvent(CalendarModel|null $calendar, array $overrides = []): CalendarEventsModel&MockObject
    {
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, array_merge(
            [
                'id' => 1,
                'title' => 'My Event',
                'enableDeregistration' => true,
                'unsubscribeLimitTstamp' => 0,
                'unsubscribeLimit' => 0,
                'addTime' => false,
                'startTime' => 0,
                'startDate' => strtotime('+10 days'),
            ],
            $overrides,
        ));

        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        return $event;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function mockCalendar(array $overrides = []): CalendarModel&MockObject
    {
        return $this->createClassWithPropertiesMock(CalendarModel::class, array_merge(
            ['allowEventBooking' => true],
            $overrides,
        ));
    }
}
