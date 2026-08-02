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

namespace Markocupic\CalendarEventBookingBundle\Tests\Validator;

use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Validator\DecimalPriceValidator;
use PHPUnit\Framework\Attributes\DataProvider;

class DecimalPriceValidatorTest extends ContaoTestCase
{
    #[DataProvider('valueProvider')]
    public function testValidate(string $value, bool $expected): void
    {
        $this->assertSame($expected, DecimalPriceValidator::validate($value));
    }

    public static function valueProvider(): iterable
    {
        yield 'zero' => ['0', true];
        yield 'zero with decimals' => ['0.00', true];
        yield 'typical price' => ['1234.56', true];
        yield 'single digit with decimals' => ['5.00', true];

        yield 'empty string' => ['', false];
        yield 'integer without decimals' => ['12', false];
        yield 'one decimal place' => ['12.5', false];
        yield 'three decimal places' => ['12.567', false];
        yield 'leading zero integer' => ['01', false];
        yield 'negative value' => ['-1.00', false];
        yield 'comma as separator' => ['1,00', false];
        yield 'non numeric' => ['abc', false];
        yield 'no leading digit' => ['.50', false];
    }
}
