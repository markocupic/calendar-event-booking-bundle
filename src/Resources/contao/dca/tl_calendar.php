<?php

/**
 * @copyright  Marko Cupic 2019
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */

/**
 * Table tl_calendar_events
 */

// Palettes
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('event_unsubscribe_legend', 'title_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(array('eventUnsubscribePage'), 'event_unsubscribe_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar');

// eventUnsubscribePage
$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventUnsubscribePage'] = array(
    'label'      => &$GLOBALS['TL_LANG']['tl_calendar']['eventUnsubscribePage'],
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => array('mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'),
    'sql'        => "int(10) unsigned NOT NULL default '0'",
    'relation'   => array('type' => 'hasOne', 'load' => 'lazy')
);

