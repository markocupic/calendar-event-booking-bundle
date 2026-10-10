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

namespace Markocupic\CalendarEventBookingBundle\Util;

use Composer\InstalledVersions;

final class ContaoVersion
{
    /**
     * Contao 6 no longer encodes user input. Values are stored raw in the database
     * and have to be encoded on output.
     */
    public static function storesRawInput(): bool
    {
        return self::isAtLeast('6.0.0');
    }

    public static function isAtLeast(string $version): bool
    {
        if (!InstalledVersions::isInstalled('contao/core-bundle')) {
            return false;
        }

        $installed = InstalledVersions::getVersion('contao/core-bundle');

        if (null === $installed || str_starts_with($installed, 'dev-')) {
            return false;
        }

        return version_compare($installed, $version, '>=');
    }
}
