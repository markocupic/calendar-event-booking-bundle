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

namespace Markocupic\CalendarEventBookingBundle\Tests\Domain\Unsubscribe;

use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Domain\Unsubscribe\ValidationResult;

class ValidationResultTest extends ContaoTestCase
{
    public function testOkCarriesValueAndNoErrorMetadata(): void
    {
        $value = new \stdClass();
        $result = ValidationResult::ok($value);

        $this->assertTrue($result->isOk());
        $this->assertFalse($result->isError());
        $this->assertTrue($result->ok);
        $this->assertSame($value, $result->value);
        $this->assertNull($result->message);
        $this->assertNull($result->severity);
        $this->assertNull($result->cssClass);
        $this->assertNull($result->flags);
    }

    public function testFailWithMinimalArguments(): void
    {
        $result = ValidationResult::fail('Something went wrong', 'ERROR');

        $this->assertFalse($result->isOk());
        $this->assertTrue($result->isError());
        $this->assertNull($result->value);
        $this->assertSame('Something went wrong', $result->message);
        $this->assertSame('ERROR', $result->severity);
        $this->assertNull($result->cssClass);
        $this->assertNull($result->flags);
    }

    public function testFailWithAllArguments(): void
    {
        $result = ValidationResult::fail(
            'Already unsubscribed',
            'INFO',
            'info booking-already-canceled',
            ['hasUnsubscribed' => true],
        );

        $this->assertTrue($result->isError());
        $this->assertNull($result->value);
        $this->assertSame('Already unsubscribed', $result->message);
        $this->assertSame('INFO', $result->severity);
        $this->assertSame('info booking-already-canceled', $result->cssClass);
        $this->assertSame(['hasUnsubscribed' => true], $result->flags);
    }
}
