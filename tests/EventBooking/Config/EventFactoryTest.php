<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
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
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;

class EventFactoryTest extends ContaoTestCase
{
    public function testCreate(): void
    {
        $event = $this->createMock(CalendarEventsModel::class);
        $connection = $this->createMock(Connection::class);
        $framework = $this->createMock(ContaoFramework::class);
        $bookingValidator = $this->createMock(BookingValidator::class);
        $factory = new EventFactory($connection, $framework, $bookingValidator);

        $this->assertInstanceOf(EventConfig::class, $factory->create($event));
    }
}
