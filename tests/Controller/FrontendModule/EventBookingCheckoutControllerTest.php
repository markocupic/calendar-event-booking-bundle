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

namespace Markocupic\CalendarEventBookingBundle\Tests\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingCheckoutController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Markocupic\ContaoFlashMessage\FlashMessage\MessageInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventBookingCheckoutControllerTest extends ContaoTestCase
{
    public function testGetBookingReturnsNullWithoutToken(): void
    {
        $method = new \ReflectionMethod(EventBookingCheckoutController::class, 'getBookingFromRequest');

        $this->assertNull($method->invoke($this->createController(), new Request()));
    }

    public function testGetBookingReturnsNullForUnknownToken(): void
    {
        $controller = $this->createControllerWithBooking('abc', null);

        $method = new \ReflectionMethod(EventBookingCheckoutController::class, 'getBookingFromRequest');

        $this->assertNull($method->invoke($controller, new Request(['bookingToken' => 'abc'])));
    }

    public function testGetBookingReturnsModelForKnownToken(): void
    {
        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['bookingToken' => 'abc']);
        $controller = $this->createControllerWithBooking('abc', $booking);

        $method = new \ReflectionMethod(EventBookingCheckoutController::class, 'getBookingFromRequest');

        $this->assertSame($booking, $method->invoke($controller, new Request(['bookingToken' => 'abc'])));
    }

    /**
     * What isCheckout() used to guard, now guarded where it is decided.
     */
    public function testInitializeStopsWithoutATokenAndForAnUnknownOne(): void
    {
        $method = new \ReflectionMethod(EventBookingCheckoutController::class, 'initialize');

        $this->assertFalse($method->invoke($this->createController(), new Request()));
        $this->assertFalse($method->invoke($this->createControllerWithBooking('abc', null), new Request(['bookingToken' => 'abc'])));
    }

    /**
     * The visitor is told "booking not found" whatever the reason. The log is the
     * only place the reason survives, and the token is the only thing to look it
     * up by - so it has to be in there.
     */
    public function testAnUnknownTokenIsWrittenToTheLog(): void
    {
        $lines = [];
        $logger = $this->createStub(LoggerInterface::class);
        $logger
            ->method('info')
            ->willReturnCallback(
                static function (string $message) use (&$lines): void {
                    $lines[] = $message;
                },
            )
        ;

        $controller = $this->createControllerWithBooking('abc', null, generalLogger: $logger);

        (new \ReflectionMethod(EventBookingCheckoutController::class, 'initialize'))
            ->invoke($controller, new Request(['bookingToken' => 'abc']))
        ;

        $this->assertCount(1, $lines);
        $this->assertStringContainsString('abc', $lines[0]);
    }

    /**
     * A request without a token is not a failed checkout but no checkout. This
     * module sits on a page that crawlers and link checkers visit like any other,
     * and one log entry per visit would bury the entries that mean something.
     */
    public function testARequestWithoutATokenIsNotLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('info')
        ;

        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $controller = $this->createController(errorLogger: $logger, generalLogger: $logger);

        (new \ReflectionMethod(EventBookingCheckoutController::class, 'initialize'))
            ->invoke($controller, new Request())
        ;
    }

    public function testInitializeReturnsFalseWhenEventBookingIsNotAllowedForCalendar(): void
    {
        // The booking, event and calendar all resolve, but the calendar no longer
        // allows event booking -> initialize() must reject before wiring a handler.
        $calendar = $this->createClassWithPropertiesStub(CalendarModel::class, ['allowEventBooking' => false]);

        $calEvent = $this->createClassWithPropertiesStub(CalendarEventsModel::class, ['published' => true]);
        $calEvent
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['bookingToken' => 'abc']);
        $booking
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calEvent)
        ;

        $controller = $this->createControllerWithBooking('abc', $booking);

        $method = new \ReflectionMethod(EventBookingCheckoutController::class, 'initialize');

        $this->assertFalse($method->invoke($controller, new Request(['bookingToken' => 'abc'])));
    }

    private function createControllerWithBooking(string $token, CalendarEventsMemberModel|null $booking, LoggerInterface|null $errorLogger = null, LoggerInterface|null $generalLogger = null): EventBookingCheckoutController
    {
        $adapter = $this->createAdapterStub(['findOneByBookingToken']);
        $adapter
            ->method('findOneByBookingToken')
            ->with($token)
            ->willReturn($booking)
        ;

        $controller = $this->createController($errorLogger, $generalLogger);
        $container = new Container();
        $container->set('contao.framework', $this->createContaoFrameworkStub([CalendarEventsMemberModel::class => $adapter]));
        $controller->setContainer($container);

        return $controller;
    }

    private function createController(LoggerInterface|null $errorLogger = null, LoggerInterface|null $generalLogger = null): EventBookingCheckoutController
    {
        return new EventBookingCheckoutController(
            $this->createStub(ContainerInterface::class),
            $this->createStub(FigureUtil::class),
            $this->createStub(MessageInterface::class),
            $this->createStub(RequestStack::class),
            $this->createStub(ScopeMatcher::class),
            $this->createStub(TranslatorInterface::class),
            $errorLogger,
            $generalLogger,
        );
    }
}
