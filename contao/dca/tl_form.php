<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/**
 * Create the calendar_event_booking palette
 */
$GLOBALS['TL_DCA']['tl_form']['palettes']['calendar_event_booking'] = '{calendar_event_booking_settings:show},isCalendarEventBookingForm;{title_legend},title,alias,jumpTo;{template_legend:hide},customTpl;{expert_legend:hide},novalidate,attributes,formID';

/**
 * Add the isCalendarEventBookingForm field to the default palette.
 */
PaletteManipulator::create()
    ->addLegend('calendar_event_booking_settings', 'title_legend', PaletteManipulator::POSITION_BEFORE, false)
    ->addField('isCalendarEventBookingForm', 'calendar_event_booking_settings', PaletteManipulator::POSITION_PREPEND)
    ->applyToPalette('default', 'tl_form');

/**
 * Add fields
 */
$GLOBALS['TL_DCA']['tl_form']['fields']['isCalendarEventBookingForm'] = [
    'eval'      => [        'submitOnChange' => true,        'tl_class'       => 'clr'    ],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => ['type' => 'boolean', 'default' => false],
];
