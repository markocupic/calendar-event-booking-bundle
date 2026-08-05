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
    public function testThrowsWhenEventIsMissing(): void
    {
        $booking = $this->mockBooking(null);

        $builder = new OptInLinkBuilder(
            $this->createContaoFrameworkStub(),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(UrlParser::class),
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event not found.');

        $builder->build($booking, 'TOK');
    }

    public function testThrowsWhenCalendarIsMissing(): void
    {
        $booking = $this->mockBooking($this->mockEvent(null));

        $builder = new OptInLinkBuilder(
            $this->createContaoFrameworkStub(),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(UrlParser::class),
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Calendar not found.');

        $builder->build($booking, 'TOK');
    }

    public function testReturnsEmptyStringWhenOptInNotRequired(): void
    {
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['requireOptIn' => false]);
        $booking = $this->mockBooking($this->mockEvent($calendar));

        $builder = new OptInLinkBuilder(
            $this->createContaoFrameworkStub(),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(UrlParser::class),
        );

        $this->assertSame('', $builder->build($booking, 'TOK'));
    }

    public function testReturnsEmptyStringWhenPageIsMissing(): void
    {
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['requireOptIn' => true, 'eventBookingOptInPage' => 3]);
        $booking = $this->mockBooking($this->mockEvent($calendar));

        $builder = new OptInLinkBuilder(
            $this->frameworkWithPage(3, null),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(UrlParser::class),
        );

        $this->assertSame('', $builder->build($booking, 'TOK'));
    }

    public function testBuildsAbsoluteOptInUrl(): void
    {
        $page = $this->createClassWithPropertiesMock(PageModel::class, ['id' => 3]);
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['requireOptIn' => true, 'eventBookingOptInPage' => 3]);
        $booking = $this->mockBooking($this->mockEvent($calendar));

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->method('generate')
            ->with($page, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/opt-in')
        ;

        $expectedParams = \sprintf('action=%s&token=%s', EventBookingOptInController::ACTION, 'TOK');

        $urlParser = $this->createMock(UrlParser::class);
        $urlParser
            ->expects($this->once())
            ->method('addQueryString')
            ->with($expectedParams, 'https://example.com/opt-in')
            ->willReturn('https://example.com/opt-in?'.$expectedParams)
        ;

        $builder = new OptInLinkBuilder($this->frameworkWithPage(3, $page), $urlGenerator, $urlParser);

        $this->assertSame('https://example.com/opt-in?'.$expectedParams, $builder->build($booking, 'TOK'));
    }

    private function mockBooking(CalendarEventsModel|null $event): CalendarEventsMemberModel&MockObject
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class);
        $booking
            ->method('getRelated')
            ->with('pid')
            ->willReturn($event)
        ;

        return $booking;
    }

    private function mockEvent(CalendarModel|null $calendar): CalendarEventsModel&MockObject
    {
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        return $event;
    }

    private function frameworkWithPage(int $pageId, PageModel|null $page): object
    {
        $adapter = $this->createAdapterMock(['findById']);
        $adapter
            ->method('findById')
            ->with($pageId)
            ->willReturn($page)
        ;

        return $this->createContaoFrameworkStub([PageModel::class => $adapter]);
    }
}
