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

namespace Markocupic\CalendarEventBookingBundle\Tests\Helper;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Controller;
use Contao\TestCase\ContaoTestCase;
use Contao\UserModel;
use Markocupic\CalendarEventBookingBundle\Helper\NotificationManager;
use Markocupic\CalendarEventBookingBundle\LinkBuilder\UnsubscribeLinkBuilder;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class NotificationHelperTest extends ContaoTestCase
{
    private NotificationManager $notificationManager;

    protected function setUp(): void
    {
        parent::setUp();

        $controllerAdapter = $this->mockAdapter(['loadLanguageFile']);

        $organizerMock = $this->mockClassWithProperties(UserModel::class);
        $organizerMock->id = (int) $this->getExpectedTokens()['organizer_id'];
        $organizerMock->name = $this->getExpectedTokens()['organizer_name'];
        $organizerMock->email = $this->getExpectedTokens()['organizer_email'];

        $userModelAdapter = $this->mockAdapter(['findById']);
        $userModelAdapter
            ->method('findById')
            ->with(1)
            ->willReturn($organizerMock)
        ;

        $adapters = [
            Controller::class => $controllerAdapter,
            UserModel::class => $userModelAdapter,
        ];

        $frameworkMock = $this->mockContaoFramework($adapters);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $notificationCenterMock = $this->createMock(NotificationCenter::class);
        $requestStackMock = $this->createMock(RequestStack::class);
        $unsubscribeLinkBuilderMock = $this->createMock(UnsubscribeLinkBuilder::class);

        $this->notificationManager = new NotificationManager(
            $frameworkMock,
            $eventDispatcherMock,
            $notificationCenterMock,
            $requestStackMock,
            $unsubscribeLinkBuilderMock,
        );
    }

    public function getExpectedTokens(): array
    {
        return [
            'member_id' => '1',
            'member_pid' => '1',
            'member_firstname' => 'John',
            'member_lastname' => 'Doe',
            'calendar_id' => '1',
            'calendar_title' => 'Test Calendar',
            'event_id' => '1',
            'event_pid' => '1',
            'event_title' => 'Test Event',
            'event_eventBookingNotificationSender' => '1',
            'organizer_id' => '1',
            'organizer_name' => 'Event Organizer',
            'organizer_email' => 'organizer@example.com',
        ];
    }

    public function testGenerateNotificationTokens(): void
    {
        $calendarMock = $this->mockClassWithProperties(CalendarModel::class);
        $calendarMock->id = (int) $this->getExpectedTokens()['calendar_id'];
        $calendarMock->title = $this->getExpectedTokens()['calendar_title'];

        $eventMock = $this->mockClassWithProperties(CalendarEventsModel::class);
        $eventMock->id = (int) $this->getExpectedTokens()['event_id'];
        $eventMock->pid = (int) $this->getExpectedTokens()['event_pid'];
        $eventMock->title = $this->getExpectedTokens()['event_title'];
        $eventMock->eventBookingNotificationSender = $this->getExpectedTokens()['event_eventBookingNotificationSender'];

        $eventMock
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendarMock)
        ;

        $booking = $this->mockClassWithProperties(CalendarEventsMemberModel::class);
        $booking->id = (int) $this->getExpectedTokens()['member_id'];
        $booking->pid = (int) $this->getExpectedTokens()['member_pid'];
        $booking->firstname = $this->getExpectedTokens()['member_firstname'];
        $booking->lastname = $this->getExpectedTokens()['member_lastname'];

        $booking
            ->method('getRelated')
            ->with('pid')
            ->willReturn($eventMock)
        ;

        $tokens = $this->notificationManager->getNotificationTokens($booking);

        foreach ($this->getExpectedTokens() as $key => $value) {
            $this->assertArrayHasKey($key, $tokens);
            $this->assertSame($value, $tokens[$key]);
        }

        $this->assertArrayNotHasKey('organizer_password', $tokens);
        $this->assertArrayNotHasKey('organizer_session', $tokens);
    }
}
