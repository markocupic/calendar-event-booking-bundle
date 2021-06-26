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

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Form;
use Contao\Input;

class CompileFormFields
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param $formId
     */
    public function compileFormFields(array $arrFields, $formId, Form $objForm): array
    {
        /** @var CalendarEventsModel $calendarEventsModelAdapter */
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        /** @var Input $inputAdapter */
        $inputAdapter = $this->framework->getAdapter(Input::class);

        // Do not list input fields under certain conditions
        if ($objForm->isCalendarEventBookingForm) {
            $objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events'));

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
