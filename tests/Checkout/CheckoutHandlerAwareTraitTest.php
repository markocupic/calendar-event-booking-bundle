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

namespace Markocupic\CalendarEventBookingBundle\Tests\Checkout;

use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Checkout\CheckoutHandlerAwareTrait;
use Markocupic\CalendarEventBookingBundle\Checkout\CheckoutHandlerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

class CheckoutHandlerAwareTraitTest extends ContaoTestCase
{
    public function testCheckoutHandlerIsNullByDefault(): void
    {
        $this->assertNull($this->createSut()->getCheckoutHandler());
    }

    public function testSetAndGetCheckoutHandler(): void
    {
        $sut = $this->createSut();
        $handler = $this->createStub(CheckoutHandlerInterface::class);

        $sut->setCheckoutHandler($handler);

        $this->assertSame($handler, $sut->getCheckoutHandler());
    }

    public function testResolveCheckoutHandlerReturnsRegisteredHandler(): void
    {
        $handler = $this->createStub(CheckoutHandlerInterface::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('has')
            ->with('default')
            ->willReturn(true)
        ;

        $container
            ->method('get')
            ->with('default')
            ->willReturn($handler)
        ;

        $this->assertSame($handler, $this->createSut()->resolveCheckoutHandler($container, 'default'));
    }

    public function testResolveCheckoutHandlerThrowsForUnknownType(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('has')
            ->with('missing')
            ->willReturn(false)
        ;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not find a checkout handler of type "missing".');

        $this->createSut()->resolveCheckoutHandler($container, 'missing');
    }

    public function testGetTypesReturnsProvidedServiceKeys(): void
    {
        $provider = $this->createStub(ServiceProviderInterface::class);
        $provider
            ->method('getProvidedServices')
            ->willReturn(['default' => CheckoutHandlerInterface::class, 'payment' => CheckoutHandlerInterface::class])
        ;

        $this->assertSame(['default', 'payment'], $this->createSut()->getTypes($provider));
    }

    private function createSut(): object
    {
        return new class() {
            use CheckoutHandlerAwareTrait;
        };
    }
}
