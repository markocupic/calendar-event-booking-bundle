<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsMemberModel;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Form;
use Contao\Input;
use Contao\Widget;

/**
 * Class ValidateFormField
 * @package Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks
 */
class ValidateFormField
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * ValidateFormField constructor.
     * @param ContaoFramework $framework
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param Widget $objWidget
     * @param $formId
     * @param array $arrForm
     * @param Form $objForm
     * @return Widget
     */
    public function validateFormField(Widget $objWidget, $formId, array $arrForm, Form $objForm): Widget
    {
        if ($objForm->isCalendarEventBookingForm)
        {
            /** @var CalendarEventsModel $calendarEventsModel */
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            /** @var CalendarEventsMemberModel $calendarEventsMemberModel */
            $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);

            /** @var Input $inputAdapter */
            $inputAdapter = $this->framework->getAdapter(Input::class);

            // Do not auto save anything to the database, this will be done manualy in the processFormData method
            $objForm->storeValues = '';

            // Check if user with submitted email has already booked
            if ($objWidget->name === 'email')
            {
                if ($objWidget->value != '')
                {
                    $objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events'));
                    if ($objEvent !== null)
                    {
                        if (!$objEvent->enableMultiBookingWithSameAddress)
                        {
                            $arrOptions = [
                                'column' => ['tl_calendar_events_member.email=?', 'tl_calendar_events_member.pid=?'],
                                'value'  => [strtolower($objWidget->value), $objEvent->id],
                            ];
                            $objMember = $calendarEventsMemberModelAdapter->findAll($arrOptions);
                            if ($objMember !== null)
                            {
                                $errorMsg = sprintf($GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'], strtolower($inputAdapter->post('email')));
                                $objWidget->addError($errorMsg);
                            }
                        }
                    }
                }
            }

            // Check maxEscortsPerMember
            if ($objWidget->name === 'escorts')
            {
                if ($objWidget->value < 0)
                {
                    $errorMsg = sprintf($GLOBALS['TL_LANG']['MSC']['enterPosIntVal']);
                    $objWidget->addError($errorMsg);
                }
                elseif ($objWidget->value > 0)
                {
                    $objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events'));
                    if ($objEvent !== null)
                    {
                        if ($objWidget->value > $objEvent->maxEscortsPerMember)
                        {
                            $errorMsg = sprintf($GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'], $objEvent->maxEscortsPerMember);
                            $objWidget->addError($errorMsg);
                        }
                    }
                }
            }
        }

        return $objWidget;
    }

}
