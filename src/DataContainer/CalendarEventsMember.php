<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Markocupic\ExportTable\Config\Config;
use Markocupic\ExportTable\Export\ExportTable;
use Markocupic\ExportTable\Writer\ByteSequence;
use Symfony\Component\HttpFoundation\RequestStack;

class CalendarEventsMember
{
    public const TABLE = 'tl_calendar_events_member';

    private RequestStack $requestStack;
    private ExportTable $exportTable;

    public function __construct(RequestStack $requestStack, ExportTable $exportTable)
    {
        $this->requestStack = $requestStack;
        $this->exportTable = $exportTable;
    }

    /**
     * Download the registration list as a csv spreadsheet.
     *
     * @Callback(table=CalendarEventsMember::TABLE, target="config.onload")
     *
     * @throws \Exception
     */
    public function downloadRegistrationList(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ('downloadRegistrationList' === $request->query->get('action')) {
            $arrSkip = ['bookingToken'];
            $arrSelectedFields = [];

            foreach (array_keys($GLOBALS['TL_DCA'][self::TABLE]['fields']) as $k) {
                if (!\in_array($k, $arrSkip, true)) {
                    $arrSelectedFields[] = $k;
                }
            }

            $exportConfig = (new Config(self::TABLE))
                ->setExportType('csv')
                ->setFilter([[self::TABLE.'.pid = ?'], [$request->query->get('id')]])
                ->setFields($arrSelectedFields)
                ->setAddHeadline(true)
                ->setHeadlineFields($arrSelectedFields)
                ->setOutputBom(ByteSequence::BOM['UTF-8'])
                ;

            $this->exportTable->run($exportConfig);
        }
    }
}
