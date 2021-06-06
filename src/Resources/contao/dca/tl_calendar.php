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

// Palettes
PaletteManipulator::create()
	->addLegend('event_unsubscribe_legend', 'title_legend', PaletteManipulator::POSITION_AFTER)
	->addField(array('eventUnsubscribePage'), 'event_unsubscribe_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_calendar');

// eventUnsubscribePage
$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventUnsubscribePage'] = array(
	'exclude'    => true,
	'inputType'  => 'pageTree',
	'foreignKey' => 'tl_page.title',
	'eval'       => array('mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'),
	'sql'        => "int(10) unsigned NOT NULL default '0'",
	'relation'   => array('type' => 'hasOne', 'load' => 'lazy')
);
