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

namespace Markocupic\CalendarEventBookingBundle\Tests\Domain\Notification;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Controller;
use Contao\TestCase\ContaoTestCase;
use Contao\UserModel;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Markocupic\CalendarEventBookingBundle\LinkBuilder\UnsubscribeLinkBuilder;
use Markocupic\CalendarEventBookingBundle\Messenger\Message\SendBookingNotificationMessage;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsOrderModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsPaymentModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use Terminal42\NotificationCenterBundle\Receipt\ReceiptCollection;

class NotificationServiceTest extends ContaoTestCase
{
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $controllerAdapter = $this->createAdapterStub(['loadLanguageFile']);

        $organizerMock = $this->createClassWithPropertiesStub(UserModel::class);
        $organizerMock->id = (int) $this->getExpectedTokens()['organizer_id'];
        $organizerMock->name = $this->getExpectedTokens()['organizer_name'];
        $organizerMock->email = $this->getExpectedTokens()['organizer_email'];

        $userModelAdapter = $this->createAdapterStub(['findById']);
        $userModelAdapter
            ->method('findById')
            ->willReturn($organizerMock)
        ;

        $adapters = [
            Controller::class => $controllerAdapter,
            UserModel::class => $userModelAdapter,
        ];

        $frameworkMock = $this->createContaoFrameworkStub($adapters);
        $eventDispatcherMock = $this->createStub(EventDispatcherInterface::class);
        $notificationCenterMock = $this->createStub(NotificationCenter::class);
        $requestStackMock = $this->createStub(RequestStack::class);
        $unsubscribeLinkBuilderMock = $this->createStub(UnsubscribeLinkBuilder::class);

        $this->notificationService = new NotificationService(
            $frameworkMock,
            $eventDispatcherMock,
            $notificationCenterMock,
            $requestStackMock,
            $unsubscribeLinkBuilderMock,
            $this->createStub(MessageBusInterface::class),
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
        $calendarMock = $this->createClassWithPropertiesStub(CalendarModel::class);
        $calendarMock->id = (int) $this->getExpectedTokens()['calendar_id'];
        $calendarMock->title = $this->getExpectedTokens()['calendar_title'];

        $eventMock = $this->createClassWithPropertiesStub(CalendarEventsModel::class);
        $eventMock->id = (int) $this->getExpectedTokens()['event_id'];
        $eventMock->pid = (int) $this->getExpectedTokens()['event_pid'];
        $eventMock->title = $this->getExpectedTokens()['event_title'];
        $eventMock->eventBookingNotificationSender = $this->getExpectedTokens()['event_eventBookingNotificationSender'];

        $eventMock
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendarMock)
        ;

        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class);
        $booking->id = (int) $this->getExpectedTokens()['member_id'];
        $booking->pid = (int) $this->getExpectedTokens()['member_pid'];
        $booking->firstname = $this->getExpectedTokens()['member_firstname'];
        $booking->lastname = $this->getExpectedTokens()['member_lastname'];

        $booking
            ->method('getRelated')
            ->with('pid')
            ->willReturn($eventMock)
        ;

        $tokens = $this->notificationService->getNotificationTokens($booking);

        foreach ($this->getExpectedTokens() as $key => $value) {
            $this->assertArrayHasKey($key, $tokens);
            $this->assertSame($value, $tokens[$key]);
        }

        $this->assertArrayNotHasKey('organizer_password', $tokens);
        $this->assertArrayNotHasKey('organizer_session', $tokens);
    }

    /**
     * Only ids go into the message: it is serialised, and the handler loads the
     * records as they are when it runs. The language is the one of the request that
     * queued it, because the worker has none.
     */
    public function testQueueNotificationDispatchesIdsAndTheLanguageOfTheRequest(): void
    {
        $request = new Request();
        $request->setLocale('de_CH');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $dispatched = [];

        $service = $this->serviceWith($this->recordingBus($dispatched), $requestStack);
        $service->queueNotification(
            5,
            $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['id' => 7]),
            $this->createClassWithPropertiesStub(CalendarEventsOrderModel::class, ['id' => 11]),
            $this->createClassWithPropertiesStub(CalendarEventsPaymentModel::class, ['id' => 13]),
        );

        $this->assertCount(1, $dispatched);
        $this->assertInstanceOf(SendBookingNotificationMessage::class, $dispatched[0]);
        $this->assertSame([5, 7, 11, 13, 'de_CH'], [$dispatched[0]->notificationId, $dispatched[0]->bookingId, $dispatched[0]->orderId, $dispatched[0]->paymentId, $dispatched[0]->locale]);
    }

    /**
     * The handler remembers sent messages by their id. Two notifications for the same
     * booking are two messages, and both have to go out.
     */
    public function testEveryQueuedNotificationGetsItsOwnId(): void
    {
        $dispatched = [];

        $service = $this->serviceWith($this->recordingBus($dispatched), new RequestStack());
        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['id' => 7]);

        $service->queueNotification(5, $booking);
        $service->queueNotification(5, $booking);

        $this->assertNotSame($dispatched[0]->messageId, $dispatched[1]->messageId);
        $this->assertNull($dispatched[0]->orderId);
        $this->assertNull($dispatched[0]->locale);
    }

    /**
     * From a cron job or a messenger worker there is no request. SendNotificationEvent
     * used to insist on one, which made sendNotification() unusable there - the
     * reason the waiting list went around this service.
     */
    public function testSendNotificationWorksWithoutARequestAndPassesTheLanguageOn(): void
    {
        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter
            ->expects($this->once())
            ->method('sendNotification')
            ->with(5, ['member_firstname' => 'Anna'], 'de_CH')
            ->willReturn(new ReceiptCollection())
        ;

        $service = new NotificationService(
            $this->createContaoFrameworkStub([]),
            $this->createStub(EventDispatcherInterface::class),
            $notificationCenter,
            new RequestStack(),
            $this->createStub(UnsubscribeLinkBuilder::class),
            $this->createStub(MessageBusInterface::class),
        );

        $service->sendNotification(5, ['member_firstname' => 'Anna'], $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['id' => 7]), 'de_CH');
    }

    private function serviceWith(MessageBusInterface $bus, RequestStack $requestStack): NotificationService
    {
        return new NotificationService(
            $this->createContaoFrameworkStub([]),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(NotificationCenter::class),
            $requestStack,
            $this->createStub(UnsubscribeLinkBuilder::class),
            $bus,
        );
    }

    /**
     * @param array<int, object> $dispatched
     */
    private function recordingBus(array &$dispatched): MessageBusInterface
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus
            ->method('dispatch')
            ->willReturnCallback(
                static function (object $message) use (&$dispatched): Envelope {
                    $dispatched[] = $message;

                    return new Envelope($message);
                },
            )
        ;

        return $bus;
    }
}
