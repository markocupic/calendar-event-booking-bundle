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

        $eventConfig->getModel()->activateWaitingList = '';
        $this->assertFalse($eventConfig->hasWaitingList());
    }

    private function getEventConfig(): EventConfig
    {
        $event = $this->mockClassWithProperties(CalendarEventsModel::class);
        $event->pid = 1;
        $event->id = 42;
        $event->alias = 'test-event';

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
        $this->assertSame('test-event', $eventConfig->get('alias'));
    }

    public function testGetModel(): void
    {
        $eventConfig = $this->getEventConfig();

        $this->assertInstanceOf(CalendarEventsModel::class, $eventConfig->getModel());
    }

    /**
     * @dataProvider provideIsWaitingListFull
     */
    public function testIsWaitingListFull(bool $expectedResult, array $input): void
    {
        $event = $this->mockClassWithProperties(CalendarEventsModel::class);
        $event->id = 42;
        $event->activateWaitingList = $input['dataEvent']['activateWaitingList'];
        $event->waitingListLimit = $input['dataEvent']['waitingListLimit'];
        $event->addEscortsToTotal = $input['dataEvent']['addEscortsToTotal'];

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly($input['expectsExactly']))
            ->method('fetchOne')
            ->withConsecutive(
                ['SELECT COUNT(id) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [$event->id, BookingState::STATE_WAITING_LIST]],
                ['SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [$event->id, BookingState::STATE_WAITING_LIST]],
            )
            ->willReturnOnConsecutiveCalls(...$input['consecutiveReturn'])
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $factory = new EventFactory($connection, $framework);
        $eventConfig = $factory->create($event);

        $this->assertSame($expectedResult, $eventConfig->isWaitingListFull());
    }

    /**
     * @dataProvider provideIsFullyBooked
     */
    public function testIsFullyBooked(bool $expectedResult, array $input): void
    {
        $event = $this->mockClassWithProperties(CalendarEventsModel::class);
        $event->pid = 1;
        $event->id = 42;
        $event->maxMembers = $input['dataEvent']['maxMembers'];
        $event->addEscortsToTotal = $input['dataEvent']['addEscortsToTotal'];

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly($input['expectsExactly']))
            ->method('fetchOne')
            ->withConsecutive(
                ['SELECT COUNT(id) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [$event->id, BookingState::STATE_CONFIRMED]],
                ['SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?', [$event->id, BookingState::STATE_CONFIRMED]],
            )
            ->willReturnOnConsecutiveCalls(...$input['consecutiveReturn'])
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $factory = new EventFactory($connection, $framework);
        $eventConfig = $factory->create($event);

        $this->assertSame($expectedResult, $eventConfig->isFullyBooked());
    }

    public function provideIsWaitingListFull()
    {
        yield 'case #1' => [
            true, // expected
            [ // input
                'dataEvent' => ['activateWaitingList' => '1', 'waitingListLimit' => 2, 'addEscortsToTotal' => '1'],
                'consecutiveReturn' => [1, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 2,
            ],
        ];

        yield 'case #2' => [
            false, // expected
            [ // input
                'dataEvent' => ['activateWaitingList' => '1', 'waitingListLimit' => 2, 'addEscortsToTotal' => ''],
                'consecutiveReturn' => [1, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 1,
            ],
        ];

        yield 'case #3' => [
            true, // expected
            [ // input
                'dataEvent' => ['activateWaitingList' => '1', 'waitingListLimit' => 3, 'addEscortsToTotal' => '1'],
                'consecutiveReturn' => [1, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 2,
            ],
        ];

        yield 'case #4' => [
            true, // expected
            [ // input
                'dataEvent' => ['activateWaitingList' => '1', 'waitingListLimit' => 1, 'addEscortsToTotal' => ''],
                'consecutiveReturn' => [1, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 1,
            ],
        ];

        yield 'case #5' => [
            false, // expected
            [ // input
                'dataEvent' => ['activateWaitingList' => '1', 'waitingListLimit' => 4, 'addEscortsToTotal' => '1'],
                'consecutiveReturn' => [1, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 2,
            ],
        ];

        yield 'case #6' => [
            false, // expected
            [ // input
                'dataEvent' => ['activateWaitingList' => '1', 'waitingListLimit' => 4, 'addEscortsToTotal' => ''],
                'consecutiveReturn' => [1, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 1,
            ],
        ];

        yield 'case #7' => [
            true, // expected
            [ // input
                'dataEvent' => ['activateWaitingList' => '', 'waitingListLimit' => 2, 'addEscortsToTotal' => '1'],
                'consecutiveReturn' => [1, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 0,
            ],
        ];
    }

    public function provideIsFullyBooked()
    {
        yield 'case #1' => [
            true, // expected
            [ // input
                'dataEvent' => ['maxMembers' => 2, 'addEscortsToTotal' => ''],
                'consecutiveReturn' => [3, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 1,
            ],
        ];

        yield 'case #2' => [
            true, // expected
            [ // input
                'dataEvent' => ['maxMembers' => 3, 'addEscortsToTotal' => '1'],
                'consecutiveReturn' => [1, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 2,
            ],
        ];

        yield 'case #3' => [
            true, // expected
            [ // input
                'dataEvent' => ['maxMembers' => 2, 'addEscortsToTotal' => '1'],
                'consecutiveReturn' => [1, 2], // [countRegistrations,countEscorts]
                'expectsExactly' => 2,
            ],
        ];

        yield 'case #4' => [
            true, // expected
            [ // input
                'dataEvent' => ['maxMembers' => 3, 'addEscortsToTotal' => ''],
                'consecutiveReturn' => [3, 0], // [countRegistrations,countEscorts]
                'expectsExactly' => 1,
            ],
        ];

        yield 'case #5' => [
            false, // expected
            [ // input
                'dataEvent' => ['maxMembers' => 4, 'addEscortsToTotal' => '1'],
                'consecutiveReturn' => [2, 1], // [countRegistrations,countEscorts]
                'expectsExactly' => 2,
            ],
        ];

        yield 'case #6' => [
            false, // expected
            [ // input
                'dataEvent' => ['maxMembers' => 3, 'addEscortsToTotal' => ''],
                'consecutiveReturn' => [2, 0], // [countRegistrations,countEscorts]
                'expectsExactly' => 1,
            ],
        ];
    }
}
