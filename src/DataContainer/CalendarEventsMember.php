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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Contao\System;
use Markocupic\ExportTable\Config\Config;
use Markocupic\ExportTable\Export\ExportTable;

class CalendarEventsMember
{

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ExportTable $exportTable,
    )
    {
        $this->system = $this->framework->getAdapter(System::class);
    }

    /**
     * @throws \Exception
     */
    #[AsCallback(table: 'tl_calendar_events_member', target: 'config.onload')]
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

            // Handle output conversion
            if ($this->system->getContainer()->getParameter('markocupic_calendar_event_booking.member_list_export.enable_output_conversion')) {
                $convertFrom = $this->system->getContainer()->getParameter('markocupic_calendar_event_booking.member_list_export.convert_from');
                $convertTo = $this->system->getContainer()->getParameter('markocupic_calendar_event_booking.member_list_export.convert_to');

                if ('utf-8' !== strtolower($convertTo)) {
                    $exportConfig->setOutputBom('');
                }

                $exportConfig->convertEncoding(true, $convertFrom, $convertTo);
            }

            $this->exportTable->run($exportConfig);
        }
    }
}
