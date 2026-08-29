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

namespace Markocupic\CalendarEventBookingBundle\Tests\Domain\Booking\Session;

use Contao\CalendarEventsModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\Session\BookingFlashStorage;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class BookingFlashStorageTest extends ContaoTestCase
{
    public function testFlashKeyConstant(): void
    {
        $this->assertSame('_event_booking', BookingFlashStorage::FLASH_KEY);
    }

    public function testAddToSessionStartsSessionAndStoresBookingData(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $storage = new BookingFlashStorage($requestStack);

        $event = $this->createClassWithPropertiesStub(CalendarEventsModel::class, ['id' => 1, 'title' => 'My Event']);
        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['id' => 7, 'firstname' => 'Jane']);
        $formData = ['email' => 'jane@example.com'];

        $storage->addToSession($event, $booking, $formData);

        $this->assertTrue($session->isStarted());

        $flash = $session->getFlashBag()->peek(BookingFlashStorage::FLASH_KEY);

        $this->assertSame(['id' => 1, 'title' => 'My Event'], $flash['eventData']);
        $this->assertSame(['id' => 7, 'firstname' => 'Jane'], $flash['memberData']);
        $this->assertSame($formData, $flash['formData']);
    }
}
