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

namespace Markocupic\CalendarEventBookingBundle\Tests\Util;

use Composer\InstalledVersions;
use Markocupic\CalendarEventBookingBundle\Util\ContaoVersion;
use PHPUnit\Framework\TestCase;

class ContaoVersionTest extends TestCase
{
    public function testComparesAgainstTheInstalledCoreBundle(): void
    {
        $this->assertTrue(ContaoVersion::isAtLeast('5.3.0'));
        $this->assertFalse(ContaoVersion::isAtLeast('99.0.0'));
    }

    public function testStoresRawInputOnlyFromContao6(): void
    {
        $installed = (string) InstalledVersions::getVersion('contao/core-bundle');

        $this->assertSame(version_compare($installed, '6.0.0', '>='), ContaoVersion::storesRawInput());
    }
}
