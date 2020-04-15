<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Form;

/**
 * Class PrepareFormData
 * @package Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks
 */
class PrepareFormData
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * PrepareFormData constructor.
     * @param ContaoFramework $framework
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param array $arrSubmitted
     * @param array $arrLabels
     * @param array $arrFields
     * @param Form $objForm
     */
    public function prepareFormData(array &$arrSubmitted, array $arrLabels, array $arrFields, Form $objForm): void
    {
        if ($objForm->isCalendarEventBookingForm)
        {
            if ($arrSubmitted['dateOfBirth'] != '')
            {
                $tstamp = strtotime($arrSubmitted['dateOfBirth']);
                if ($tstamp !== false)
                {
                    $arrSubmitted['dateOfBirth'] = $tstamp;
                }
            }
        }
    }



}
