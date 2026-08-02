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
use Markocupic\CalendarEventBookingBundle\Checkout\CheckoutResult;
use Symfony\Component\HttpFoundation\Response;

class CheckoutResultTest extends ContaoTestCase
{
    public function testDefaults(): void
    {
        $result = new CheckoutResult('default');

        $this->assertSame('default', $result->getCheckoutType());
        $this->assertSame([], $result->getData());
        $this->assertFalse($result->hasResponse());
        $this->assertNull($result->getResponse());
    }

    public function testConstructorArguments(): void
    {
        $response = new Response();
        $result = new CheckoutResult('payment', ['booking' => ['id' => 1]], $response);

        $this->assertSame('payment', $result->getCheckoutType());
        $this->assertSame(['booking' => ['id' => 1]], $result->getData());
        $this->assertTrue($result->hasResponse());
        $this->assertSame($response, $result->getResponse());
    }

    public function testSetters(): void
    {
        $result = new CheckoutResult('default');

        $result->setCheckoutType('payment');
        $result->setData(['foo' => 'bar']);

        $response = new Response();
        $result->setResponse($response);

        $this->assertSame('payment', $result->getCheckoutType());
        $this->assertSame(['foo' => 'bar'], $result->getData());
        $this->assertTrue($result->hasResponse());
        $this->assertSame($response, $result->getResponse());
    }

    public function testResponseCanBeReset(): void
    {
        $result = new CheckoutResult('default', [], new Response());

        $this->assertTrue($result->hasResponse());

        $result->setResponse(null);

        $this->assertFalse($result->hasResponse());
        $this->assertNull($result->getResponse());
    }
}
