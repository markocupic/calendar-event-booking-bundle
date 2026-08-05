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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingCheckoutController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Markocupic\ContaoFlashMessage\FlashMessage\MessageInterface;
use Psr\Container\ContainerInterface;
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
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['bookingToken' => 'abc']);
        $controller = $this->createControllerWithBooking('abc', $booking);

        $method = new \ReflectionMethod(EventBookingCheckoutController::class, 'getBookingFromRequest');

        $this->assertSame($booking, $method->invoke($controller, new Request(['bookingToken' => 'abc'])));
    }

    public function testIsCheckoutReflectsBookingPresence(): void
    {
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class, ['bookingToken' => 'abc']);
        $controller = $this->createControllerWithBooking('abc', $booking);

        $method = new \ReflectionMethod(EventBookingCheckoutController::class, 'isCheckout');

        $this->assertTrue($method->invoke($controller, new Request(['bookingToken' => 'abc'])));
        $this->assertFalse($method->invoke($this->createController(), new Request()));
    }

    private function createControllerWithBooking(string $token, CalendarEventsMemberModel|null $booking): EventBookingCheckoutController
    {
        $adapter = $this->createAdapterMock(['findOneByBookingToken']);
        $adapter
            ->method('findOneByBookingToken')
            ->with($token)
            ->willReturn($booking)
        ;

        $controller = $this->createController();
        $container = new Container();
        $container->set('contao.framework', $this->createContaoFrameworkStub([CalendarEventsMemberModel::class => $adapter]));
        $controller->setContainer($container);

        return $controller;
    }

    private function createController(): EventBookingCheckoutController
    {
        return new EventBookingCheckoutController(
            $this->createMock(ContainerInterface::class),
            $this->createMock(FigureUtil::class),
            $this->createMock(MessageInterface::class),
            $this->createMock(RequestStack::class),
            $this->createMock(ScopeMatcher::class),
            $this->createMock(TranslatorInterface::class),
        );
    }
}
