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

namespace Markocupic\CalendarEventBookingBundle\Tests\Domain\Booking;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\BookingCapacity;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\WaitingListPromotionProcessor;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class WaitingListPromotionProcessorTest extends ContaoTestCase
{
    public function testCheckWaitingListDoesNothingWhenDisabled(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory
            ->expects($this->never())
            ->method('createLock')
        ;

        $processor = $this->createProcessor(
            framework: $framework,
            lockFactory: $lockFactory,
            autoPromotion: false,
        );

        $processor->checkWaitingList();
    }

    public function testPromoteSendsNotificationAndLogsWhenRowAffected(): void
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['id' => 7, 'log' => '']);
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['waitingListAdvancementNotification' => 5]);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        $update = null;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->willReturnCallback(
                static function (string $table, array $data, array $criteria) use (&$update): int {
                    $update = ['table' => $table, 'data' => $data, 'criteria' => $criteria];

                    return 1;
                },
            )
        ;

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService
            ->method('getNotificationTokens')
            ->with($booking)
            ->willReturn(['recipient_email' => 'x@example.com'])
        ;

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter
            ->expects($this->once())
            ->method('sendNotification')
            ->with(5, ['recipient_email' => 'x@example.com'])
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Moved booking ID 7'))
        ;

        $processor = $this->createProcessor(
            connection: $connection,
            notificationCenter: $notificationCenter,
            notificationService: $notificationService,
            logger: $logger,
        );

        $processor->promoteBookingFromWaitingList($booking, $event, 'cron');

        $this->assertSame('tl_calendar_events_member', $update['table']);
        $this->assertSame(['id' => 7], $update['criteria']);
        $this->assertSame(0, $update['data']['waitingList']);

        // The promotion is written into the booking's own log as well, because
        // tl_log is rotated away long before anyone asks why this person moved up.
        $this->assertStringStartsWith(date('Y-m-d H:i').' Moved from the waiting list', $update['data']['log']);
        $this->assertStringContainsString('Context: cron.', $update['data']['log']);
    }

    /**
     * Whatever the booking's log already holds is kept - the new line goes
     * underneath it. See Util\LogBuilder.
     */
    public function testPromoteAppendsToAnExistingLog(): void
    {
        $booking = $this->createClassWithPropertiesMock(
            CalendarEventsMemberModel::class,
            ['id' => 7, 'log' => '2026-08-01 09:00 Opt-in confirmed by the participant via the confirmation link.'],
        );

        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['waitingListAdvancementNotification' => 0]);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        $update = null;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('update')
            ->willReturnCallback(
                static function (string $table, array $data) use (&$update): int {
                    $update = $data;

                    return 1;
                },
            )
        ;

        $this->createProcessor(connection: $connection)
            ->promoteBookingFromWaitingList($booking, $event, 'cron')
        ;

        $lines = explode("\n", $update['log']);

        $this->assertCount(2, $lines);
        $this->assertSame('2026-08-01 09:00 Opt-in confirmed by the participant via the confirmation link.', $lines[0]);
    }

    public function testPromoteSkipsNotificationWhenCalendarHasNoneConfigured(): void
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['id' => 7]);
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['waitingListAdvancementNotification' => 0]);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('update')
            ->willReturn(1)
        ;

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter
            ->expects($this->never())
            ->method('sendNotification')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
        ;

        $processor = $this->createProcessor(
            connection: $connection,
            notificationCenter: $notificationCenter,
            logger: $logger,
        );

        $processor->promoteBookingFromWaitingList($booking, $event, 'cron');
    }

    public function testPromoteDoesNothingWhenNoRowAffected(): void
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['id' => 7]);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('update')
            ->willReturn(0)
        ;

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter
            ->expects($this->never())
            ->method('sendNotification')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('info')
        ;

        $processor = $this->createProcessor(
            connection: $connection,
            notificationCenter: $notificationCenter,
            logger: $logger,
        );

        $processor->promoteBookingFromWaitingList($booking, $event, 'cron');
    }

    private function createProcessor(Connection|null $connection = null, ContaoFramework|null $framework = null, EventDispatcherInterface|null $dispatcher = null, LockFactory|null $lockFactory = null, NotificationCenter|null $notificationCenter = null, NotificationService|null $notificationService = null, LoggerInterface|null $logger = null, bool $autoPromotion = true): WaitingListPromotionProcessor
    {
        return new WaitingListPromotionProcessor(
            $connection ?? $this->createMock(Connection::class),
            $framework ?? $this->createMock(ContaoFramework::class),
            $dispatcher ?? $this->createMock(EventDispatcherInterface::class),
            $this->createMock(BookingCapacity::class),
            $lockFactory ?? $this->createMock(LockFactory::class),
            $notificationCenter ?? $this->createMock(NotificationCenter::class),
            $notificationService ?? $this->createMock(NotificationService::class),
            $this->createMock(RequestStack::class),
            $autoPromotion,
            $logger,
        );
    }
}
