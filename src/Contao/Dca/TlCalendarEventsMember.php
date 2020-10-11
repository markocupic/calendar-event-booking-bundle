<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2020 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Contao\Dca;

use Contao\Input;
use Markocupic\ExportTable\ExportTable;

/**
 * Class TlCalendarEventsMember.
 */
class TlCalendarEventsMember
{
    /**
     * @throws \Exception
     */
    public function downloadRegistrationList(): void
    {
        // Download the registration list as a csv spreadsheet
        if ('downloadRegistrationList' === Input::get('act')) {
            $opt = [];

            // Add fields
            $arrSkip = ['bookingToken'];
            $opt['arrSelectedFields'] = [];

            foreach (array_keys($GLOBALS['TL_DCA']['tl_calendar_events_member']['fields']) as $k) {
                if (!\in_array($k, $arrSkip, true)) {
                    $opt['arrSelectedFields'][] = $k;
                }
            }

            $opt['exportType'] = 'csv';
            $opt['arrFilter'] = [['tl_calendar_events_member.pid=?'], [Input::get('id')]];
            ExportTable::exportTable('tl_calendar_events_member', $opt);
            exit;
        }
    }
}
