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

namespace Markocupic\CalendarEventBookingBundle\Tests\Template;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\BookingCapacity;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\EventStatusResolver;
use Markocupic\CalendarEventBookingBundle\Security\User\FrontendUserAccessor;
use Markocupic\CalendarEventBookingBundle\Template\TemplateDataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateDataProviderTest extends ContaoTestCase
{
    public function testGetDataAggregatesEventInformation(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 9]);
        $user = $this->createMock(FrontendUser::class);

        $calendar = $this->mockClassWithProperties(CalendarModel::class, ['id' => 3]);
        $calendar
            ->method('current')
            ->willReturnSelf()
        ;

        $event = $this->mockClassWithProperties(CalendarEventsModel::class, ['id' => 1]);
        $event
            ->method('current')
            ->willReturnSelf()
        ;

        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $provider = new TemplateDataProvider(
            $this->frontendUserAccessor(true, $user),
            $this->bookingCapacity(isFullyBooked: false, freeSpots: 5, bookingCount: 2),
            $this->eventStatusResolver(true),
        );

        $data = $provider->getData($event, $request);

        $this->assertSame($event, $data['event']);
        $this->assertSame($calendar, $data['calendar']);
        $this->assertTrue($data['canRegister']);
        $this->assertFalse($data['isFullyBooked']);
        $this->assertSame(5, $data['freeSpotsCount']);
        $this->assertSame(2, $data['bookingCount']);
        $this->assertTrue($data['hasLoggedInUser']);
        $this->assertSame($user, $data['loggedInUser']);
        $this->assertSame($page, $data['page']);
    }

    public function testGetDataHandlesMissingCalendarAndGuestUser(): void
    {
        $event = $this->mockClassWithProperties(CalendarEventsModel::class, ['id' => 1]);
        $event
            ->method('current')
            ->willReturnSelf()
        ;

        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;

        $provider = new TemplateDataProvider(
            $this->frontendUserAccessor(false, null),
            $this->bookingCapacity(isFullyBooked: true, freeSpots: 0, bookingCount: 10),
            $this->eventStatusResolver(false),
        );

        $data = $provider->getData($event, new Request());

        $this->assertNull($data['calendar']);
        $this->assertFalse($data['canRegister']);
        $this->assertFalse($data['hasLoggedInUser']);
        $this->assertNull($data['loggedInUser']);
        $this->assertNull($data['page']);
    }

    public function testAddDataWritesAllKeysToTemplate(): void
    {
        $event = $this->mockClassWithProperties(CalendarEventsModel::class, ['id' => 1]);
        $event
            ->method('current')
            ->willReturnSelf()
        ;

        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;

        $provider = new TemplateDataProvider(
            $this->frontendUserAccessor(false, null),
            $this->bookingCapacity(isFullyBooked: false, freeSpots: 3, bookingCount: 1),
            $this->eventStatusResolver(true),
        );

        $template = new FragmentTemplate(
            'test',
            static fn (FragmentTemplate $t, Response|null $r = null): Response => $r ?? new Response(),
        );

        $provider->addData($template, $event, new Request());

        $data = $template->getData();

        $this->assertSame($event, $data['event']);
        $this->assertTrue($data['canRegister']);
        $this->assertSame(3, $data['freeSpotsCount']);
        $this->assertSame(1, $data['bookingCount']);
    }

    private function frontendUserAccessor(bool $hasUser, FrontendUser|null $user): FrontendUserAccessor
    {
        $accessor = $this->createMock(FrontendUserAccessor::class);
        $accessor
            ->method('hasLoggedInFrontendUser')
            ->willReturn($hasUser)
        ;

        $accessor
            ->method('getLoggedInFrontendUser')
            ->willReturn($user)
        ;

        return $accessor;
    }

    private function bookingCapacity(bool $isFullyBooked, int $freeSpots, int $bookingCount): BookingCapacity
    {
        $capacity = $this->createMock(BookingCapacity::class);
        $capacity
            ->method('isFullyBooked')
            ->willReturn($isFullyBooked)
        ;

        $capacity
            ->method('getFreeSpotsCount')
            ->willReturn($freeSpots)
        ;

        $capacity
            ->method('getBookingCount')
            ->willReturn($bookingCount)
        ;

        return $capacity;
    }

    private function eventStatusResolver(bool $canRegister): EventStatusResolver
    {
        $resolver = $this->createMock(EventStatusResolver::class);
        $resolver
            ->method('canRegister')
            ->willReturn($canRegister)
        ;

        return $resolver;
    }
}
