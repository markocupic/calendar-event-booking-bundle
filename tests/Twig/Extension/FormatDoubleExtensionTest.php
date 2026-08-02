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

namespace Markocupic\CalendarEventBookingBundle\Tests\Twig\Extension;

use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Twig\Extension\FormatDoubleExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FormatDoubleExtensionTest extends ContaoTestCase
{
    #[DataProvider('formatProvider')]
    public function testFormatDouble(mixed $number, int $decimals, string $decPoint, string $thousandsSep, string $expected): void
    {
        $extension = new FormatDoubleExtension();

        $this->assertSame($expected, $extension->formatDouble($number, $decimals, $decPoint, $thousandsSep));
    }

    public static function formatProvider(): iterable
    {
        yield 'default two decimals' => [1234.5, 2, '.', '', '1234.50'];
        yield 'rounds to two decimals' => [1234.567, 2, '.', '', '1234.57'];
        yield 'string input is cast to float' => ['12.5', 2, '.', '', '12.50'];
        yield 'thousands separator' => [1234567.89, 2, '.', "'", "1'234'567.89"];
        yield 'zero decimals' => [1234.56, 0, '.', '', '1235'];
        yield 'comma decimal point' => [12.5, 2, ',', '', '12,50'];
    }

    public function testFormatDoubleUsesDefaultArguments(): void
    {
        $this->assertSame('1234.50', (new FormatDoubleExtension())->formatDouble(1234.5));
    }

    public function testExposesFormatDoubleFilterAndFunction(): void
    {
        $extension = new FormatDoubleExtension();

        $filterNames = array_map(static fn (TwigFilter $filter): string => $filter->getName(), $extension->getFilters());
        $functionNames = array_map(static fn (TwigFunction $function): string => $function->getName(), $extension->getFunctions());

        $this->assertContains('format_double', $filterNames);
        $this->assertContains('format_double', $functionNames);
    }
}
