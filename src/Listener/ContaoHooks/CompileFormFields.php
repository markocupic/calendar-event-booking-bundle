<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Form;
use Contao\Input;

/**
 * Class CompileFormFields
 * @package Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks
 */
class CompileFormFields
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * CompileFormFields constructor.
     * @param ContaoFramework $framework
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param array $arrFields
     * @param $formId
     * @param Form $objForm
     * @return array
     */
    public function compileFormFields(array $arrFields, $formId, Form $objForm): array
    {
        /** @var  CalendarEventsModel $calendarEventsModelAdapter */
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        /** @var Input $inputAdapter */
        $inputAdapter = $this->framework->getAdapter(Input::class);

        // Do not list input fields under certain conditions
        if ($objForm->isCalendarEventBookingForm)
        {
            $objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events'));
            if ($objEvent !== null)
            {
                $maxEscorts = $objEvent->maxEscortsPerMember;
                if ($maxEscorts < 1)
                {
                    unset($arrFields['escorts']);
                }
            }
        }

        return $arrFields;
    }

}
