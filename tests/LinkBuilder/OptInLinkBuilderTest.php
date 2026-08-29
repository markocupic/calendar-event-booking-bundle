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

namespace Markocupic\CalendarEventBookingBundle\Tests\LinkBuilder;

use Codefog\HasteBundle\UrlParser;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingOptInController;
use Markocupic\CalendarEventBookingBundle\LinkBuilder\OptInLinkBuilder;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OptInLinkBuilderTest extends ContaoTestCase
{
    public function testThrowsWhenEventNotFound(): void
    {
        $booking = $this->mockBooking(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event not found.');

        $this->createLinkBuilder()->build($booking, 'tok');
    }

    public function testThrowsWhenCalendarNotFound(): void
    {
        $event = $this->mockEvent(null);
        $booking = $this->mockBooking($event);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Calendar not found.');

        $this->createLinkBuilder()->build($booking, 'tok');
    }

    public function testReturnsEmptyStringWhenEventBookingIsNotAllowed(): void
    {
        $booking = $this->mockBookingChain(['allowEventBooking' => false, 'requireOptIn' => true]);

        $this->assertSame('', $this->createLinkBuilder()->build($booking, 'tok'));
    }

    public function testReturnsEmptyStringWhenOptInIsNotRequired(): void
    {
        $booking = $this->mockBookingChain(['allowEventBooking' => true, 'requireOptIn' => false]);

        $this->assertSame('', $this->createLinkBuilder()->build($booking, 'tok'));
    }

    public function testReturnsEmptyStringWhenOptInPageIsMissing(): void
    {
        $booking = $this->mockBookingChain([
            'allowEventBooking' => true,
            'requireOptIn' => true,
            'eventBookingOptInPage' => 3,
        ]);

        // The configured opt-in page cannot be resolved -> no link.
        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(3)
            ->willReturn(null)
        ;

        $linkBuilder = $this->createLinkBuilder(
            framework: $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]),
        );

        $this->assertSame('', $linkBuilder->build($booking, 'tok'));
    }

    public function testBuildsAbsoluteOptInUrlWithActionAndToken(): void
    {
        $booking = $this->mockBookingChain([
            'allowEventBooking' => true,
            'requireOptIn' => true,
            'eventBookingOptInPage' => 3,
        ]);

        $page = $this->createClassWithPropertiesMock(PageModel::class, ['id' => 3]);

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->method('findById')
            ->with(3)
            ->willReturn($page)
        ;

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($page, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/opt-in')
        ;

        $urlParser = $this->createMock(UrlParser::class);
        $urlParser
            ->method('addQueryString')
            ->willReturnCallback(static fn (string $query, string $url): string => $url.'?'.$query)
        ;

        $linkBuilder = $this->createLinkBuilder(
            framework: $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]),
            contentUrlGenerator: $contentUrlGenerator,
            urlParser: $urlParser,
        );

        $expectedQuery = \sprintf('action=%s&token=%s', EventBookingOptInController::ACTION, 'my-token');

        $this->assertSame(
            'https://example.com/opt-in?'.$expectedQuery,
            $linkBuilder->build($booking, 'my-token'),
        );
    }

    /**
     * @param array<string, mixed> $calendarProperties
     */
    private function mockBookingChain(array $calendarProperties): CalendarEventsMemberModel&MockObject
    {
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, $calendarProperties);

        return $this->mockBooking($this->mockEvent($calendar));
    }

    private function mockBooking(CalendarEventsModel|null $event): CalendarEventsMemberModel&MockObject
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['id' => 5]);
        $booking
            ->method('getRelated')
            ->with('pid')
            ->willReturn($event)
        ;

        return $booking;
    }

    private function mockEvent(CalendarModel|null $calendar): CalendarEventsModel&MockObject
    {
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['id' => 10]);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        return $event;
    }

    private function createLinkBuilder(object|null $framework = null, ContentUrlGenerator|null $contentUrlGenerator = null, UrlParser|null $urlParser = null): OptInLinkBuilder
    {
        return new OptInLinkBuilder(
            $framework ?? $this->createContaoFrameworkStub(),
            $contentUrlGenerator ?? $this->createMock(ContentUrlGenerator::class),
            $urlParser ?? $this->createMock(UrlParser::class),
        );
    }
}
