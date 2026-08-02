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

namespace Markocupic\CalendarEventBookingBundle\Tests\EventListener\ContaoHook;

use Contao\TestCase\ContaoTestCase;
use Contao\Widget;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook\DecimalPriceRegexpListener;

class DecimalPriceRegexpListenerTest extends ContaoTestCase
{
    public function testReturnsTrueForValidDecimalPrice(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget
            ->expects($this->never())
            ->method('addError')
        ;

        $listener = new DecimalPriceRegexpListener();

        $this->assertTrue($listener(DecimalPriceRegexpListener::REGEXP_NAME, '1234.56', $widget));
    }

    public function testAddsErrorAndReturnsFalseForInvalidValue(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget
            ->expects($this->once())
            ->method('addError')
        ;

        $listener = new DecimalPriceRegexpListener();

        $this->assertFalse($listener(DecimalPriceRegexpListener::REGEXP_NAME, '12.5', $widget));
    }

    public function testIgnoresOtherRegexpNames(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget
            ->expects($this->never())
            ->method('addError')
        ;

        $listener = new DecimalPriceRegexpListener();

        $this->assertFalse($listener('some_other_rgxp', 'whatever', $widget));
    }
}
