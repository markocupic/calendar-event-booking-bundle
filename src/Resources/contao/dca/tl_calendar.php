<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

// Palettes
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('event_unsubscribe_legend', 'title_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(['eventUnsubscribePage'], 'event_unsubscribe_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar');

// eventUnsubscribePage
$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventUnsubscribePage'] = [
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'        => "int(10) unsigned NOT NULL default '0'",
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy']
];

