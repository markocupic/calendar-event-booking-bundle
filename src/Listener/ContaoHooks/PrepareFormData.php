<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\Controller;
use Contao\Form;

class PrepareFormData
{
    public function prepareFormData(array &$arrSubmitted, array $arrLabels, array $arrFields, Form $objForm): void
    {
        if ($objForm->isCalendarEventBookingForm) {
            Controller::loadDataContainer('tl_calendar_events_member');
            $dca = $GLOBALS['TL_DCA']['tl_calendar_events_member'];

            foreach ($arrSubmitted as $k => $v) {
                // Convert date strings to timestamps
                if (isset($dca['fields'][$k]['eval']['rgxp']) && 'date' === $dca['fields'][$k]['eval']['rgxp'] || 'datim' === $dca['fields'][$k]['eval']['rgxp']) {
                    if (!empty($v)) {
                        if (false !== ($tstamp = strtotime($arrSubmitted[$k]))) {
                            $arrSubmitted[$k] = $tstamp;
                        }
                    }
                }
            }
        }
    }
}
