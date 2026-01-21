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

namespace Markocupic\CalendarEventBookingBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Contao\System;
use Markocupic\ExportTable\Config\Config;
use Markocupic\ExportTable\Export\ExportTable;

class CalendarEventsMember
{
    private Adapter $system;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ExportTable $exportTable,
    ) {
        $this->system = $this->framework->getAdapter(System::class);
    }

    /**
     * @throws \Exception
     */
    #[AsCallback(table: 'tl_calendar_events_member', target: 'config.onload')]
    public function downloadEventBookings(): void
    {
        // Download the booking list as a csv spreadsheet
        if ('downloadEventBookings' === Input::get('action')) {
            // Add fields
            $arrSkip = ['bookingToken'];
            $arrSelectedFields = [];

            foreach (array_keys($GLOBALS['TL_DCA']['tl_calendar_events_member']['fields']) as $k) {
                if (!\in_array($k, $arrSkip, true)) {
                    $arrSelectedFields[] = $k;
                }
            }

            $filterQuery = ['tl_calendar_events_member.pid = ?'];
            $filterParams = [Input::get('id')];

            // Add custom filters via url parameter (filter=base64encoded_query)
            $filter = Input::get('filter', true);

            if (!empty($filter)) {
                $filter = base64_decode($filter, true);
                $filter = preg_replace('/\./', '%2E', $filter);
                $customFilters = $this->parseQueryPreserveDots($filter);

                foreach ($customFilters as $key => $value) {
                    $filterQuery[] = "$key = ?";
                    $filterParams[] = $value;
                }
            }

            $exportConfig = (new Config('tl_calendar_events_member'))
                ->setExportType('csv')
                ->setFilter([implode(' AND ', $filterQuery), $filterParams])
                ->setFields($arrSelectedFields)
                ->setSortBy('addedOn')
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

    public function parseQueryPreserveDots(string $query): array
    {
        $result = [];

        foreach (explode('&', $query) as $pair) {
            if ('' === $pair) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');

            $key = urldecode($key);
            $value = urldecode($value);

            $result[$key] = $value;
        }

        return $result;
    }
}
