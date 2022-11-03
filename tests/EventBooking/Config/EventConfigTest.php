<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Tests\EventBooking\Config;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Symfony\Component\HttpFoundation\Request;

class EventConfigTest extends ContaoTestCase
{

    public function testHasWaitingList(): void
    {
        $eventConfig = $this->getEventConfig();

        $eventConfig->getModel()->activateWaitingList = '1';
        $this->assertTrue($eventConfig->hasWaitingList());

        $eventConfig->getModel()->activateWaitingList = 1;
        $this->assertTrue($eventConfig->hasWaitingList());

        $eventConfig->getModel()->activateWaitingList = true;
        $this->assertTrue($eventConfig->hasWaitingList());

        $eventConfig->getModel()->activateWaitingList = '0';
        $this->assertFalse($eventConfig->hasWaitingList());

        $eventConfig->getModel()->activateWaitingList = '';
        $this->assertFalse($eventConfig->hasWaitingList());

        $eventConfig->getModel()->activateWaitingList = 0;
        $this->assertFalse($eventConfig->hasWaitingList());

        $eventConfig->getModel()->activateWaitingList = false;
        $this->assertFalse($eventConfig->hasWaitingList());
    }

    public function testGetClassProperty(): void
    {
        $eventConfig = $this->getEventConfig();
        $eventConfig->getModel()->activateWaitingList = '1';

        $this->assertSame('1', $eventConfig->get('activateWaitingList'));
    }

    public function testGetModel(): void
    {
        $eventConfig = $this->getEventConfig();

        $this->assertInstanceOf(CalendarEventsModel::class, $eventConfig->getModel());
    }

    public function testIsWaitingListFull(): void
    {
        $event = $this->mockClassWithProperties(CalendarEventsModel::class);
        $event->pid = 1;
        $event->id = 42;
        $event->alias = 'foo-event';
        $event->activateWaitingList = '1';
        $event->waitingListLimit = 2;
        $event->addEscortsToTotal = '1';

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchOne')
            ->withConsecutive(
                    ['SELECT COUNT(id) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [42, BookingState::STATE_WAITING_LIST]],
                    ['SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [42, BookingState::STATE_WAITING_LIST]],
            )
            ->willReturnOnConsecutiveCalls(
                1,2,
            )
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $factory = new EventFactory($connection, $framework);
        $eventConfig = $factory->create($event);

        $this->assertTrue($eventConfig->isWaitingListFull());
    }

    private function getEventConfig(): EventConfig
    {
        $event = $this->mockClassWithProperties(CalendarEventsModel::class);
        $event->pid = 1;
        $event->id = 42;
        $event->alias = 'foo-event';

        $connection = $this->createMock(Connection::class);
        $framework = $this->createMock(ContaoFramework::class);
        $factory = new EventFactory($connection, $framework);

        return $factory->create($event);
    }

}
