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

use Contao\Form;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;

class CompileFormFields
{
    /**
     * @var EventRegistration
     */
    private $eventRegistration;

    public function __construct(EventRegistration $eventRegistration)
    {
        $this->eventRegistration = $eventRegistration;
    }

    /**
     * @param $formId
     */
    public function compileFormFields(array $arrFields, $formId, Form $objForm): array
    {
        // Do not list input fields under certain conditions
        if ($objForm->isCalendarEventBookingForm) {
            $objEvent = $this->eventRegistration->getEventFromCurrentUrl();

            if (null !== $objEvent) {
                $maxEscorts = $objEvent->maxEscortsPerMember;

                if ($maxEscorts < 1) {
                    unset($arrFields['escorts']);
                }
            }
        }

        return $arrFields;
    }
}
