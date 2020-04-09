<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

// Add the fields to the palettes
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('calendar_event_booking_settings', 'title_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE, false)
    ->addField('isCalendarEventBookingForm', 'calendar_event_booking_settings', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_PREPEND)
    ->applyToPalette('default', 'tl_form');

$GLOBALS['TL_DCA']['tl_form']['fields']['isCalendarEventBookingForm'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['isCalendarEventBookingForm'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => false, 'tl_class' => 'clr'],
    'sql'       => "char(1) NOT NULL default ''"
];

