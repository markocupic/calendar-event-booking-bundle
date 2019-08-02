<?php

/**
 * @copyright  Marko Cupic 2019
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */


// Add the fields to the palettes
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('calendar_event_booking_settings', 'title_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE, false)
    ->addField('isCalendarEventBookingForm', 'calendar_event_booking_settings', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_PREPEND)
    ->applyToPalette('default', 'tl_form');


$GLOBALS['TL_DCA']['tl_form']['fields']['isCalendarEventBookingForm'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['isCalendarEventBookingForm'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => false, 'tl_class' => 'clr'),
    'sql'       => "char(1) NOT NULL default ''"
);

