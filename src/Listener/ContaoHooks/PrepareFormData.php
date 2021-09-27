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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\Form;

class PrepareFormData
{
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function prepareFormData(array &$arrSubmitted, array $arrLabels, array $arrFields, Form $objForm): void
    {
        if ($objForm->isCalendarEventBookingForm) {
            /** @var Controller $controllerAdapter */
            $controllerAdapter = $this->framework->getAdapter(Controller::class);

            /** @var Date $dateAdapter */
            $dateAdapter = $this->framework->getAdapter(Date::class);

            $controllerAdapter->loadDataContainer('tl_calendar_events_member');

            foreach ($arrSubmitted as $fieldname => $varValue) {
                $rgxp = $GLOBALS['TL_DCA']['tl_calendar_events_member']['fields'][$fieldname]['eval']['rgxp'] ?? null;

                // Convert date formats into timestamps
                if (null !== $varValue && '' !== $varValue && \in_array($rgxp, ['date', 'time', 'datim'], true)) {
                    try {
                        $objDate = new Date($varValue, $dateAdapter->getFormatFromRgxp($rgxp));
                        $arrSubmitted[$fieldname] = $objDate->tstamp;
                    } catch (\OutOfBoundsException $e) {
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['invalidDate'], $varValue));
                    }
                }
            }
        }
    }
}
