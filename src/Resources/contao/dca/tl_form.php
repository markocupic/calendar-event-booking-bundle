<?php

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Add the fields to the palettes
PaletteManipulator::create()
	->addLegend('calendar_event_booking_settings', 'title_legend', PaletteManipulator::POSITION_BEFORE, false)
	->addField('isCalendarEventBookingForm', 'calendar_event_booking_settings', PaletteManipulator::POSITION_PREPEND)
	->applyToPalette('default', 'tl_form')
;

// Fields
$GLOBALS['TL_DCA']['tl_form']['fields']['isCalendarEventBookingForm'] = array(
	'eval'      => array('submitOnChange' => false, 'tl_class' => 'clr'),
	'exclude'   => true,
	'filter'    => true,
	'inputType' => 'checkbox',
	'sql'       => "char(1) NOT NULL default ''",
);
