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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Form;

class PrepareFormData
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function prepareFormData(array &$arrSubmitted, array $arrLabels, array $arrFields, Form $objForm): void
    {
        if ($objForm->isCalendarEventBookingForm) {
            if (!empty($arrSubmitted['dateOfBirth'])) {
                $tstamp = strtotime($arrSubmitted['dateOfBirth']);

                if (false !== $tstamp) {
                    $arrSubmitted['dateOfBirth'] = $tstamp;
                }
            }
        }
    }
}
