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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;

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

        $dataEvent = [];
        $consecutiveReturn = [];
        $assert = [];
        $expectsExactly = [];

        // Case #0
        $i=0;
        $dataEvent[$i] = ['activateWaitingList' => '1', 'waitingListLimit' => 2, 'addEscortsToTotal' => '1'];
        $consecutiveReturn[$i] = [1, 2];
        $expectsExactly[$i] = 2;
        $assert[$i] = true;

        // Case #1
        $i++;
        $dataEvent[$i] = ['activateWaitingList' => '1', 'waitingListLimit' => 2, 'addEscortsToTotal' => ''];
        $consecutiveReturn[$i] = [1, 2];
        $expectsExactly[$i] = 1;
        $assert[$i] = false;

        // Case #2
        $i++;
        $dataEvent[$i] = ['activateWaitingList' => '1', 'waitingListLimit' => 3, 'addEscortsToTotal' => '1'];
        $consecutiveReturn[$i] = [1, 2];
        $expectsExactly[$i] = 2;
        $assert[$i] = true;

        // Case #3
        $i++;
        $dataEvent[$i] = ['activateWaitingList' => '1', 'waitingListLimit' => 1, 'addEscortsToTotal' => ''];
        $consecutiveReturn[$i] = [1, 2];
        $expectsExactly[$i] = 1;
        $assert[$i] = true;

        // Case #4
        $i++;
        $dataEvent[$i] = ['activateWaitingList' => '1', 'waitingListLimit' => 4, 'addEscortsToTotal' => '1'];
        $consecutiveReturn[$i] = [1, 2];
        $expectsExactly[$i] = 2;
        $assert[$i] = false;

        // Case #5
        $i++;
        $dataEvent[$i] = ['activateWaitingList' => '1', 'waitingListLimit' => 4, 'addEscortsToTotal' => ''];
        $consecutiveReturn[$i] = [1, 2];
        $expectsExactly[$i] = 1;
        $assert[$i] = false;

        // Case #6
        $i++;
        $dataEvent[$i] = ['activateWaitingList' => '', 'waitingListLimit' => 2, 'addEscortsToTotal' => '1'];
        $consecutiveReturn[$i] = [];
        $expectsExactly[$i] = 0;
        $assert[$i] = true;

        for ($i = 0; $i < \count($dataEvent); ++$i) {
            $event->activateWaitingList = $dataEvent[$i]['activateWaitingList'];
            $event->waitingListLimit = $dataEvent[$i]['waitingListLimit'];
            $event->addEscortsToTotal = $dataEvent[$i]['addEscortsToTotal'];

            $connection = $this->createMock(Connection::class);
            $connection
                ->expects($this->exactly($expectsExactly[$i]))
                ->method('fetchOne')
                ->withConsecutive(
                    ['SELECT COUNT(id) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [$event->id, BookingState::STATE_WAITING_LIST]],
                    ['SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [$event->id, BookingState::STATE_WAITING_LIST]],
                )
                ->willReturnOnConsecutiveCalls(...$consecutiveReturn[$i])
            ;

            $framework = $this->createMock(ContaoFramework::class);
            $factory = new EventFactory($connection, $framework);
            $eventConfig = $factory->create($event);

            $this->assertSame($assert[$i], $eventConfig->isWaitingListFull());
        }
    }

    public function testIsFullyBooked(): void
    {
        $event = $this->mockClassWithProperties(CalendarEventsModel::class);
        $event->pid = 1;
        $event->id = 42;
        $event->alias = 'foo-event';

        $dataEvent = [];
        $consecutiveReturn = [];
        $assert = [];
        $expectsExactly = [];

        // Case #0
        $i=0;
        $dataEvent[$i] = ['maxMembers' => 2, 'addEscortsToTotal' => '1'];
        $consecutiveReturn[$i] = [1, 2];
        $expectsExactly[$i] = 2;
        $assert[$i] = true;

        // Case #1
        $i++;
        $dataEvent[$i] = ['maxMembers' => 2, 'addEscortsToTotal' => ''];
        $consecutiveReturn[$i] = [3, 2];
        $expectsExactly[$i] = 1;
        $assert[$i] = true;

        // Case #2
        $i++;
        $dataEvent[$i] = ['maxMembers' => 3, 'addEscortsToTotal' => '1'];
        $consecutiveReturn[$i] = [1, 2];
        $expectsExactly[$i] = 2;
        $assert[$i] = true;

        // Case #3
        $i++;
        $dataEvent[$i] = ['maxMembers' => 3, 'addEscortsToTotal' => ''];
        $consecutiveReturn[$i] = [3, 0];
        $expectsExactly[$i] = 1;
        $assert[$i] = true;

        // Case #4
        $i++;
        $dataEvent[$i] = ['maxMembers' => 4, 'addEscortsToTotal' => '1'];
        $consecutiveReturn[$i] = [2, 1];
        $expectsExactly[$i] = 2;
        $assert[$i] = false;

        // Case #5
        $i++;
        $dataEvent[$i] = ['maxMembers' => 3, 'addEscortsToTotal' => ''];
        $consecutiveReturn[$i] = [2, 0];
        $expectsExactly[$i] = 1;
        $assert[$i] = false;

        for ($i = 0; $i < \count($dataEvent); ++$i) {
            $event->maxMembers = $dataEvent[$i]['maxMembers'];
            $event->addEscortsToTotal = $dataEvent[$i]['addEscortsToTotal'];

            $connection = $this->createMock(Connection::class);
            $connection
                ->expects($this->exactly($expectsExactly[$i]))
                ->method('fetchOne')
                ->withConsecutive(
                    ['SELECT COUNT(id) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [$event->id, BookingState::STATE_CONFIRMED]],
                    ['SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [$event->id, BookingState::STATE_CONFIRMED]],
                )
                ->willReturnOnConsecutiveCalls(...$consecutiveReturn[$i])
            ;

            $framework = $this->createMock(ContaoFramework::class);
            $factory = new EventFactory($connection, $framework);
            $eventConfig = $factory->create($event);

            $this->assertSame($assert[$i], $eventConfig->isFullyBooked());
        }
    }
}
