<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;

// Table config
$GLOBALS['TL_DCA']['tl_calendar']['config']['ctable'][] = 'tl_calendar_events_member';

// Palettes
PaletteManipulator::create()
    ->addLegend('booking_options_legend', 'source_legend', PaletteManipulator::POSITION_BEFORE)
    ->addLegend('waiting_list_legend', 'source_legend', PaletteManipulator::POSITION_BEFORE)
    ->addLegend('notification_legend', 'source_legend', PaletteManipulator::POSITION_BEFORE)
    ->addLegend('event_unsubscribe_legend', 'source_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['activateBookingForm'], 'booking_options_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['street', 'postal', 'city'], 'location', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_calendar_events');

// Selector
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'activateBookingForm';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'activateWaitingList';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'activateBookingNotification';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'activateDeregistration';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'activateUnsubscribeNotification';

// Subpalettes
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['activateBookingForm'] = 'minMembers,maxMembers,maxEscortsPerMember,addEscortsToTotal,bookingStartDate,bookingEndDate,allowDuplicateEmail,bookingState,,activateWaitingList,activateBookingNotification,activateDeregistration';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['activateWaitingList'] = 'waitingListLimit';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['activateBookingNotification'] = 'eventBookingNotification,eventBookingNotificationSender';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['activateDeregistration'] = 'unsubscribeLimit,unsubscribeLimitTstamp,activateUnsubscribeNotification';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['activateUnsubscribeNotification'] = 'eventUnsubscribeNotification,eventUnsubscribeNotificationSender';

// Operations
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations']['registrations'] = [
    'href'  => 'do=calendar&table=tl_calendar_events_member',
    'icon'  => 'bundles/markocupiccalendareventbooking/icons/group.png',
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['registrations'],
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

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['activateBookingForm'] = [
    'eval'      => ['submitOnChange' => true, 'isBoolean' => true, 'tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['overrideCalendarBookingSettings'] = [
    'eval'      => ['submitOnChange' => true, 'isBoolean' => true, 'tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['allowDuplicateEmail'] = [
    'eval'      => ['isBoolean' => true, 'tl_class' => 'clr m12'],
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
        BookingState::STATE_NOT_CONFIRMED,
        BookingState::STATE_CONFIRMED,
        BookingState::STATE_WAITING_LIST,
        BookingState::STATE_REJECTED,
        BookingState::STATE_UNSUBSCRIBED,
    ],
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'search'    => true,
    'sorting'   => true,
    'sql'       => "varchar(64) NOT NULL default '".BookingState::STATE_CONFIRMED."'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingStartDate'] = [
    'eval'      => ['rgxp' => 'datim', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'clr w50 wizard'],
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => 'int(10) unsigned NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingEndDate'] = [
    'eval'      => ['rgxp' => 'datim', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => 'int(10) unsigned NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['minMembers'] = [
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxMembers'] = [
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxEscortsPerMember'] = [
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['addEscortsToTotal'] = [
    'eval'      => ['isBoolean' => true, 'tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['activateWaitingList'] = [
    'eval'      => ['submitOnChange' => true, 'isBoolean' => true, 'tl_class' => 'clr'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['waitingListLimit'] = [
    'eval'      => ['rgxp' => 'digit', 'tl_class' => 'clr'],
    'exclude'   => true,
    'inputType' => 'text',
    'sql'       => "smallint(3) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['activateBookingNotification'] = [
    'eval'      => ['submitOnChange' => true, 'isBoolean' => true, 'tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotification'] = [
    'eval'       => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'],
    'exclude'    => true,
    'foreignKey' => 'tl_nc_notification.title',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'sql'        => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationSender'] = [
    'eval'       => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'clr'],
    'exclude'    => true,
    'foreignKey' => 'tl_user.name',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'sql'        => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['activateDeregistration'] = [
    'eval'      => ['submitOnChange' => true, 'isBoolean' => true, 'tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimit'] = [
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

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['activateUnsubscribeNotification'] = [
    'eval'      => ['submitOnChange' => true, 'isBoolean' => true, 'tl_class' => 'clr m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventUnsubscribeNotification'] = [
    'eval'       => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'],
    'exclude'    => true,
    'foreignKey' => 'tl_nc_notification.title',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'sql'        => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventUnsubscribeNotificationSender'] = [
    'eval'       => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'clr'],
    'exclude'    => true,
    'foreignKey' => 'tl_user.name',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'sql'        => "int(10) unsigned NOT NULL default '0'",
];
