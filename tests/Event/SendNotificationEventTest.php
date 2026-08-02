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

namespace Markocupic\CalendarEventBookingBundle\Tests\Event;

use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Event\SendNotificationEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;

class SendNotificationEventTest extends ContaoTestCase
{
    public function testExposesConstructorArguments(): void
    {
        $booking = $this->mockClassWithProperties(CalendarEventsMemberModel::class, ['id' => 1]);
        $request = new Request();

        $event = new SendNotificationEvent(42, ['recipient_email' => 'a@example.com'], $booking, $request);

        $this->assertSame(42, $event->getNotificationId());
        $this->assertSame(['recipient_email' => 'a@example.com'], $event->getTokens());
        $this->assertSame($booking, $event->getBooking());
        $this->assertSame($request, $event->getRequest());
    }

    public function testTokensCanBeReplaced(): void
    {
        $event = $this->createEvent();

        $event->setTokens(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $event->getTokens());
    }

    public function testShouldSendDefaultsToTrueAndCanBeToggled(): void
    {
        $event = $this->createEvent();

        $this->assertTrue($event->shouldSend());

        $event->setShouldSend(false);

        $this->assertFalse($event->shouldSend());
    }

    private function createEvent(): SendNotificationEvent
    {
        return new SendNotificationEvent(1, [], $this->mockClassWithProperties(CalendarEventsMemberModel::class), new Request());
    }
}
