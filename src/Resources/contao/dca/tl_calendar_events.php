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
    ->applyToPalette('default', 'tl_calendar_events')
;

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
    'eval'      => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
    'exclude'   => true,
    'flag'      => 1,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['postal'] = array(
    'eval'      => array('maxlength' => 32, 'tl_class' => 'w50'),
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "varchar(32) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['city'] = array(
    'eval'      => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
    'exclude'   => true,
    'flag'      => 1,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['addBookingForm'] = array(
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableMultiBookingWithSameAddress'] = array(
    'eval'      => array('tl_class' => 'clr m12'),
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingStartDate'] = array(
    'default'   => null,
    'eval'      => array('rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'clr w50 wizard'),
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => "int(10) unsigned NULL",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingEndDate'] = array(
    'default'   => null,
    'eval'      => array('rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'),
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => "int(10) unsigned NULL",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['minMembers'] = array(
    'default'   => 0,
    'eval'      => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxMembers'] = array(
    'default'   => 0,
    'eval'      => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxEscortsPerMember'] = array(
    'default'   => 0,
    'eval'      => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['includeEscortsWhenCalculatingRegCount'] = array(
    'default'   => 0,
    'eval'      => array('tl_class' => 'clr m12'),
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableNotificationCenter'] = array(
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationCenterIds'] = array(
    'eval'       => array('mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'),
    'exclude'    => true,
    'foreignKey' => 'tl_nc_notification.title',
    'inputType'  => 'select',
    'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
    'search'     => true,
    'sql'        => "blob NULL",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationSender'] = array(
    'default'    => 0,
    'eval'       => array('mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'clr'),
    'exclude'    => true,
    'foreignKey' => 'tl_user.name',
    'inputType'  => 'select',
    'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
    'search'     => true,
    'sql'        => "int(10) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableDeregistration'] = array(
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimit'] = array(
    'default'   => 0,
    'eval'      => array('rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'),
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'options'   => range(0, 720),
    'sorting'   => true,
    'sql'       => "int(10) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimitTstamp'] = array(
    'default'       => null,
    'eval'          => array('rgxp' => 'datim', 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'),
    'exclude'       => true,
    'inputType'     => 'text',
    'save_callback' => array(array(TlCalendarEvents::class, 'saveUnsubscribeLimitTstamp')),
    'sorting'       => true,
    'sql'           => "int(10) unsigned NULL",
);
