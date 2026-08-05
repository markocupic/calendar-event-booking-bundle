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
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Domain\Unsubscribe\UnsubscribeValidator;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class UnsubscribeValidatorTest extends ContaoTestCase
{
    private const string TRANS_DOMAIN = 'mc_calendar_event_booking';

    private TranslatorInterface&MockObject $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => $id)
        ;
    }

    public function testFailsWhenBookingIsNull(): void
    {
        $result = $this->validator()->validate(null, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.invalid_uuid', $result->message);
        $this->assertSame(SeverityLevel::ERROR->value, $result->severity);
        $this->assertSame('error booking-not-found', $result->cssClass);
    }

    public function testFailsWhenEventIsMissing(): void
    {
        $booking = $this->mockBooking(false, null);

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.event_not_found', $result->message);
        $this->assertSame('error event-not-found', $result->cssClass);
    }

    public function testAlreadyCanceledBookingReturnsInfo(): void
    {
        $booking = $this->mockBooking(true, $this->mockEvent());

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.info.already_unsubscribed', $result->message);
        $this->assertSame(SeverityLevel::INFO->value, $result->severity);
        $this->assertSame('info booking-already-canceled', $result->cssClass);
        $this->assertSame(['hasUnsubscribed' => true], $result->flags);
    }

    public function testCanceledBookingWithUnsubscribedFlagShowsSuccess(): void
    {
        $booking = $this->mockBooking(true, $this->mockEvent());

        $result = $this->validator()->validate($booking, true, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.info.unsubscribe_success', $result->message);
    }

    public function testFailsWhenDeregistrationIsDisabled(): void
    {
        $booking = $this->mockBooking(false, $this->mockEvent(['enableDeregistration' => false]));

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.unsubscription_not_allowed', $result->message);
        $this->assertSame('error unsubscription-not-allowed', $result->cssClass);
    }

    public function testFailsWhenUnsubscribeLimitHasExpired(): void
    {
        $booking = $this->mockBooking(false, $this->mockEvent(['unsubscribeLimitTstamp' => time() - 3600]));

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isError());
        $this->assertSame('mod_unsubscribe.error.unsubscription_limit_expired', $result->message);
        $this->assertSame('error unsubscription-limit-expired', $result->cssClass);
    }

    public function testReturnsOkForValidBooking(): void
    {
        $event = $this->mockEvent();
        $booking = $this->mockBooking(false, $event);

        $result = $this->validator()->validate($booking, false, self::TRANS_DOMAIN);

        $this->assertTrue($result->isOk());
        $this->assertSame($event, $result->value);
    }

    /**
     * @param array<string, mixed> $eventProps
     */
    #[DataProvider('limitExpiredProvider')]
    public function testIsLimitExpired(array $eventProps, bool $expected): void
    {
        $method = new \ReflectionMethod(UnsubscribeValidator::class, 'isLimitExpired');

        $this->assertSame($expected, $method->invoke($this->validator(), $this->mockEvent($eventProps)));
    }

    public static function limitExpiredProvider(): iterable
    {
        yield 'explicit timestamp in the past is expired' => [
            ['unsubscribeLimitTstamp' => 1],
            true,
        ];

        yield 'explicit timestamp far in the future is not expired' => [
            ['unsubscribeLimitTstamp' => strtotime('+10 years')],
            false,
        ];

        yield 'day limit, event within the limit is expired' => [
            ['unsubscribeLimitTstamp' => 0, 'unsubscribeLimit' => 5, 'addTime' => false, 'startDate' => strtotime('+2 days')],
            true,
        ];

        yield 'day limit, event beyond the limit is not expired' => [
            ['unsubscribeLimitTstamp' => 0, 'unsubscribeLimit' => 5, 'addTime' => false, 'startDate' => strtotime('+30 days')],
            false,
        ];

        yield 'with start time, still within the limit is not expired' => [
            ['unsubscribeLimitTstamp' => 0, 'unsubscribeLimit' => 1, 'addTime' => true, 'startDate' => strtotime('+9 days'), 'startTime' => strtotime('+10 days')],
            false,
        ];

        yield 'with start time, past the limit is expired' => [
            ['unsubscribeLimitTstamp' => 0, 'unsubscribeLimit' => 1, 'addTime' => true, 'startDate' => strtotime('+1 hour'), 'startTime' => strtotime('+2 hours')],
            true,
        ];
    }

    private function validator(): UnsubscribeValidator
    {
        return new UnsubscribeValidator($this->translator);
    }

    private function mockBooking(bool $canceled, CalendarEventsModel|null $event): CalendarEventsMemberModel&MockObject
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['canceled' => $canceled]);
        $booking
            ->method('getRelated')
            ->with('pid')
            ->willReturn($event)
        ;

        return $booking;
    }

    /**
     * Builds an event that is, by default, open for deregistration and far enough
     * in the future so that the unsubscribe limit has not expired.
     *
     * @param array<string, mixed> $overrides
     */
    private function mockEvent(array $overrides = []): CalendarEventsModel&MockObject
    {
        $props = array_merge(
            [
                'title' => 'My Event',
                'enableDeregistration' => true,
                'unsubscribeLimitTstamp' => 0,
                'unsubscribeLimit' => 0,
                'addTime' => false,
                'startDate' => strtotime('+30 days'),
                'startTime' => 0,
            ],
            $overrides,
        );

        return $this->createClassWithPropertiesMock(CalendarEventsModel::class, $props);
    }
}
