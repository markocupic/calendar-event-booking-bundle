<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\DataContainer;

use Contao\Input;
use Markocupic\ExportTable\Config\Config;
use Markocupic\ExportTable\Export\ExportTable;

class CalendarEventsMember
{
    private ExportTable $exportTable;

    public function __construct(ExportTable $exportTable)
    {
        $this->exportTable = $exportTable;
    }

    /**
     * @throws \Exception
     */
    public function downloadEventRegistrations(): void
    {
        // Download the registration list as a csv spreadsheet
        if ('downloadEventRegistrations' === Input::get('action')) {
            // Add fields
            $arrSkip = ['bookingToken'];
            $arrSelectedFields = [];

            foreach (array_keys($GLOBALS['TL_DCA']['tl_calendar_events_member']['fields']) as $k) {
                if (!\in_array($k, $arrSkip, true)) {
                    $arrSelectedFields[] = $k;
                }
            }

            $exportConfig = (new Config('tl_calendar_events_member'))
                ->setExportType('csv')
                ->setFilter([['tl_calendar_events_member.pid = ?'], [Input::get('id')]])
                ->setFields($arrSelectedFields)
                ->setAddHeadline(true)
                ->setHeadlineFields($arrSelectedFields)
                ;

            $this->exportTable->run($exportConfig);
        }
    }
}
