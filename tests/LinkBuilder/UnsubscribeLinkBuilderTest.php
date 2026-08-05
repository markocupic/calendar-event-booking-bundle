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
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingUnsubscribeController;
use Markocupic\CalendarEventBookingBundle\LinkBuilder\UnsubscribeLinkBuilder;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UnsubscribeLinkBuilderTest extends ContaoTestCase
{
    public function testThrowsWhenEventIsMissing(): void
    {
        $booking = $this->mockBooking(null);

        $builder = $this->builder($this->createContaoFrameworkStub(), $this->createMock(ContentUrlGenerator::class), $this->createMock(UrlParser::class));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event not found.');

        $builder->build($booking);
    }

    public function testReturnsEmptyStringWhenDeregistrationDisabled(): void
    {
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['enableDeregistration' => false]);
        $booking = $this->mockBooking($event);

        $builder = $this->builder($this->createContaoFrameworkStub(), $this->createMock(ContentUrlGenerator::class), $this->createMock(UrlParser::class));

        $this->assertSame('', $builder->build($booking));
    }

    public function testThrowsWhenCalendarIsMissing(): void
    {
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['enableDeregistration' => true]);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;
        $booking = $this->mockBooking($event);

        $builder = $this->builder($this->createContaoFrameworkStub(), $this->createMock(ContentUrlGenerator::class), $this->createMock(UrlParser::class));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Calendar not found.');

        $builder->build($booking);
    }

    public function testReturnsEmptyStringWhenPageIsMissing(): void
    {
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['eventUnsubscribePage' => 4]);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['enableDeregistration' => true]);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;
        $booking = $this->mockBooking($event);

        $builder = $this->builder($this->frameworkWithPage(4, null), $this->createMock(ContentUrlGenerator::class), $this->createMock(UrlParser::class));

        $this->assertSame('', $builder->build($booking));
    }

    public function testBuildsAbsoluteUnsubscribeUrl(): void
    {
        $page = $this->createClassWithPropertiesMock(PageModel::class, ['id' => 4]);
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, ['eventUnsubscribePage' => 4]);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, ['enableDeregistration' => true]);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;
        $booking = $this->mockBooking($event, 'TOK');

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->method('generate')
            ->with($page, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/unsubscribe')
        ;

        $expectedParams = \sprintf('action=%s&bookingToken=%s', EventBookingUnsubscribeController::ACTION, 'TOK');

        $urlParser = $this->createMock(UrlParser::class);
        $urlParser
            ->expects($this->once())
            ->method('addQueryString')
            ->with($expectedParams, 'https://example.com/unsubscribe')
            ->willReturn('https://example.com/unsubscribe?'.$expectedParams)
        ;

        $builder = $this->builder($this->frameworkWithPage(4, $page), $urlGenerator, $urlParser);

        $this->assertSame('https://example.com/unsubscribe?'.$expectedParams, $builder->build($booking));
    }

    private function mockBooking(CalendarEventsModel|null $event, string $bookingToken = ''): CalendarEventsMemberModel&MockObject
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['bookingToken' => $bookingToken]);
        $booking
            ->method('getRelated')
            ->with('pid')
            ->willReturn($event)
        ;

        return $booking;
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

    private function builder(object $framework, ContentUrlGenerator $urlGenerator, UrlParser $urlParser): UnsubscribeLinkBuilder
    {
        return new UnsubscribeLinkBuilder($framework, $urlGenerator, $urlParser);
    }
}
