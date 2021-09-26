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
use Markocupic\CalendarEventBookingBundle\Contao\Dca\TlCalendarEvents;

// Table config
$GLOBALS['TL_DCA']['tl_calendar']['config']['ctable'][] = 'tl_calendar_events_member';

// Overwrite child record callback
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['child_record_callback'] = array('Markocupic\CalendarEventBookingBundle\Contao\Dca\TlCalendarEvents', 'listEvents');

// Palettes
PaletteManipulator::create()
	->addLegend('booking_options_legend', 'details_legend', PaletteManipulator::POSITION_AFTER)
	->addLegend('notification_center_legend', 'booking_options_legend', PaletteManipulator::POSITION_AFTER)
	->addLegend('event_unsubscribe_legend', 'notification_center_legend', PaletteManipulator::POSITION_AFTER)
	->addField(array('addBookingForm'), 'booking_options_legend', PaletteManipulator::POSITION_APPEND)
	->addField(array('enableNotificationCenter'), 'notification_center_legend', PaletteManipulator::POSITION_APPEND)
	->addField(array('enableDeregistration'), 'event_unsubscribe_legend', PaletteManipulator::POSITION_APPEND)
	->addField(array('street', 'postal', 'city'), 'location', PaletteManipulator::POSITION_AFTER)
	->applyToPalette('default', 'tl_calendar_events');

// Selector
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'addBookingForm';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'enableNotificationCenter';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'enableDeregistration';

// Subpalettes
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['addBookingForm'] = 'minMembers,maxMembers,maxEscortsPerMember,includeEscortsWhenCalculatingRegCount,bookingStartDate,bookingEndDate,enableMultiBookingWithSameAddress';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['enableNotificationCenter'] = 'eventBookingNotificationCenterIds,eventBookingNotificationSender';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['enableDeregistration'] = 'unsubscribeLimit,unsubscribeLimitTstamp';

// Callbacks
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['onsubmit_callback'][] = array(TlCalendarEvents::class, 'adjustBookingDate');

// Operations
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations']['registrations'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['registrations'],
	'href'  => 'do=calendar&table=tl_calendar_events_member',
	'icon'  => 'bundles/markocupiccalendareventbooking/icons/group.png',
);

// Fields
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['street'] = array(
	'exclude'   => true,
	'search'    => true,
	'sorting'   => true,
	'flag'      => 1,
	'inputType' => 'text',
	'eval'      => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
	'sql'       => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['postal'] = array(
	'exclude'   => true,
	'search'    => true,
	'inputType' => 'text',
	'eval'      => array('maxlength' => 32, 'tl_class' => 'w50'),
	'sql'       => "varchar(32) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['city'] = array(
	'exclude'   => true,
	'search'    => true,
	'sorting'   => true,
	'flag'      => 1,
	'inputType' => 'text',
	'eval'      => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
	'sql'       => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['addBookingForm'] = array(
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
	'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableMultiBookingWithSameAddress'] = array(
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'clr m12'),
	'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingStartDate'] = array(
	'default'   => null,
	'exclude'   => true,
	'inputType' => 'text',
	'eval'      => array('rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'clr w50 wizard'),
	'sql'       => "int(10) unsigned NULL",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingEndDate'] = array(
	'default'   => null,
	'exclude'   => true,
	'inputType' => 'text',
	'eval'      => array('rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'),
	'sql'       => "int(10) unsigned NULL",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['minMembers'] = array(
	'default'   => 0,
	'exclude'   => true,
	'search'    => true,
	'default'   => 0,
	'inputType' => 'text',
	'eval'      => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
	'sql'       => "smallint(5) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxMembers'] = array(
	'default'   => 0,
	'exclude'   => true,
	'search'    => true,
	'default'   => 0,
	'inputType' => 'text',
	'eval'      => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
	'sql'       => "smallint(5) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxEscortsPerMember'] = array(
	'default'   => 0,
	'exclude'   => true,
	'search'    => true,
	'default'   => 0,
	'inputType' => 'text',
	'eval'      => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
	'sql'       => "smallint(5) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['includeEscortsWhenCalculatingRegCount'] = array(
	'exclude'   => true,
	'search'    => true,
	'default'   => 0,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'clr m12'),
	'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableNotificationCenter'] = array(
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
	'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationCenterIds'] = array(
	'exclude'    => true,
	'search'     => true,
	'inputType'  => 'select',
	'foreignKey' => 'tl_nc_notification.title',
	'eval'       => array('mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'),
	'sql'        => "blob NULL",
	'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationSender'] = array(
	'default'    => 0,
	'exclude'    => true,
	'search'     => true,
	'inputType'  => 'select',
	'foreignKey' => 'tl_user.name',
	'eval'       => array('mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'clr'),
	'sql'        => "int(10) unsigned NOT NULL default '0'",
	'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableDeregistration'] = array(
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
	'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimit'] = array(
	'default'   => 0,
	'exclude'   => true,
	'filter'    => true,
	'inputType' => 'select',
	'options'   => range(0, 720),
	'eval'      => array('rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'),
	'sql'       => "int(10) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimitTstamp'] = array(
	'default'       => null,
	'exclude'       => true,
	'inputType'     => 'text',
	'eval'          => array('rgxp' => 'datim', 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'),
	'sql'           => "int(10) unsigned NULL",
	'save_callback' => array(array(TlCalendarEvents::class, 'saveUnsubscribeLimitTstamp'))
);
