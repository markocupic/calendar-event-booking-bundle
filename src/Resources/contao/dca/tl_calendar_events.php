<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Markocupic\CalendarEventBookingBundle\Booking\BookingState;

// Table config
$GLOBALS['TL_DCA']['tl_calendar']['config']['ctable'][] = 'tl_calendar_events_member';

// Palettes
PaletteManipulator::create()
    ->addLegend('booking_options_legend', 'details_legend', PaletteManipulator::POSITION_AFTER)
    ->addLegend('notification_center_legend', 'booking_options_legend', PaletteManipulator::POSITION_AFTER)
    ->addLegend('event_unsubscribe_legend', 'notification_center_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['addBookingForm'], 'booking_options_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['enableNotificationCenter'], 'notification_center_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['enableDeregistration'], 'event_unsubscribe_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['street', 'postal', 'city'], 'location', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_calendar_events');

// Selector
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'addBookingForm';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'enableNotificationCenter';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'enableDeregistration';

// Subpalettes
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['addBookingForm'] = 'minMembers,maxMembers,maxEscortsPerMember,includeEscortsWhenCalculatingRegCount,bookingStartDate,bookingEndDate,enableMultiBookingWithSameAddress,bookingState';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['enableNotificationCenter'] = 'eventBookingNotificationCenterIds,eventBookingNotificationSender';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['enableDeregistration'] = 'unsubscribeLimit,unsubscribeLimitTstamp';

// Operations
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations']['registrations'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['registrations'],
    'href'  => 'do=calendar&table=tl_calendar_events_member',
    'icon'  => 'bundles/markocupiccalendareventbooking/icons/group.png',
];

// Fields
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['street'] = [
    'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
    'exclude'   => true,
    'flag'      => 1,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['postal'] = [
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['city'] = [
    'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
    'exclude'   => true,
    'flag'      => 1,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['addBookingForm'] = [
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableMultiBookingWithSameAddress'] = [
    'eval'      => ['tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingState'] = [
    'eval'      => ['tl_class' => 'w50', 'mandatory' => true],
    'filter'    => true,
    'inputType' => 'select',
    'options'   => [
        BookingState::STATE_UNDEFINED,
        BookingState::STATE_WAITING_FOR_RESPONSE,
        BookingState::STATE_CONFIRMED,
        BookingState::STATE_WAITING_LIST,
        BookingState::STATE_REJECTED,
        BookingState::STATE_UNSUBSCRIBED,
    ],
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'search'    => true,
    'sorting'   => true,
    'sql'       => "varchar(64) NOT NULL default '".BookingState::STATE_UNDEFINED."'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingStartDate'] = [
    'default'   => null,
    'eval'      => ['rgxp' => 'datim', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'clr w50 wizard'],
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => 'int(10) unsigned NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingEndDate'] = [
    'default'   => null,
    'eval'      => ['rgxp' => 'datim', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => 'int(10) unsigned NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['minMembers'] = [
    'default'   => 0,
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxMembers'] = [
    'default'   => 0,
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxEscortsPerMember'] = [
    'default'   => 0,
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['includeEscortsWhenCalculatingRegCount'] = [
    'default'   => 0,
    'eval'      => ['tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableNotificationCenter'] = [
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationCenterIds'] = [
    'eval'       => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'],
    'exclude'    => true,
    'foreignKey' => 'tl_nc_notification.title',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'sql'        => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationSender'] = [
    'default'    => 0,
    'eval'       => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'clr'],
    'exclude'    => true,
    'foreignKey' => 'tl_user.name',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'sql'        => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableDeregistration'] = [
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimit'] = [
    'default'   => 0,
    'eval'      => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'options'   => range(0, 720),
    'sorting'   => true,
    'sql'       => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimitTstamp'] = [
    'default'   => null,
    'eval'      => ['rgxp' => 'datim', 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => 'int(10) unsigned NULL',
];
