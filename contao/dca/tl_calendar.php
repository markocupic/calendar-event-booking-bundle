<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;

PaletteManipulator::create()
    ->addLegend('booking_options_legend', 'title_legend', PaletteManipulator::POSITION_AFTER)
    ->addLegend('event_unsubscribe_legend', 'booking_options_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['eventUnsubscribePage', 'eventUnsubscribeNotification', 'eventUnsubscribeNotificationSender'], 'event_unsubscribe_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['allowDuplicateEmail', 'bookingState', 'calculateTotalFrom', 'addEscortsToTotal', 'waitingListLimit', 'eventBookingNotification', 'eventBookingNotificationSender'], 'booking_options_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar');

$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventUnsubscribePage'] = [
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'        => "int(10) unsigned NOT NULL default '0'",
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['calculateTotalFrom'] = [
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => [BookingState::STATE_NOT_CONFIRMED, BookingState::STATE_CONFIRMED, BookingState::STATE_UNDEFINED],
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'eval'      => ['mandatory' => true, 'multiple' => true, 'chosen' => true, 'tl_class' => 'clr w50'],
    'sql'       => "varchar(255) NOT NULL default '".serialize([BookingState::STATE_CONFIRMED])."'",
];

// This field is used to override the twin input field in tl_calendar_events
$GLOBALS['TL_DCA']['tl_calendar']['fields']['addEscortsToTotal'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['isBoolean' => true, 'tl_class' => 'w50 m12 override_event'],
    'sql'       => "char(1) NOT NULL default ''",
];

// This field is used to override the twin input field in tl_calendar_events
$GLOBALS['TL_DCA']['tl_calendar']['fields']['allowDuplicateEmail'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['isBoolean' => true, 'tl_class' => 'clr m12 override_event'],
    'sql'       => "char(1) NOT NULL default ''",
];

// This field is used to override the twin input field in tl_calendar_events
$GLOBALS['TL_DCA']['tl_calendar']['fields']['bookingState'] = [
    'filter'    => true,
    'inputType' => 'select',
    'options'   => BookingState::ALL,
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'search'    => true,
    'sorting'   => true,
    'eval'      => ['tl_class' => 'clr w50 override_event', 'mandatory' => true],
    'sql'       => "varchar(64) NOT NULL default '".BookingState::STATE_CONFIRMED."'",
];

// This field is used to override the twin input field in tl_calendar_events
$GLOBALS['TL_DCA']['tl_calendar']['fields']['waitingListLimit'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'digit', 'tl_class' => 'clr w50 override_event'],
    'sql'       => "smallint(3) unsigned NOT NULL default '0'",
];

// This field is used to override the twin input field in tl_calendar_events
$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventBookingNotification'] = [
    'exclude'    => true,
    'foreignKey' => 'tl_nc_notification.title',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'eval'       => ['mandatory' => false, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr w50 override_event'],
    'sql'        => 'blob NULL',
];

// This field is used to override the twin input field in tl_calendar_events
$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventBookingNotificationSender'] = [
    'exclude'    => true,
    'foreignKey' => 'tl_user.name',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'eval'       => ['mandatory' => false, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50 override_event'],
    'sql'        => "int(10) unsigned NOT NULL default '0'",
];

// This field is used to override the twin input field in tl_calendar_events
$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventUnsubscribeNotification'] = [
    'exclude'    => true,
    'foreignKey' => 'tl_nc_notification.title',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'eval'       => ['mandatory' => false, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr w50 override_event'],
    'sql'        => 'blob NULL',
];

// This field is used to override the twin input field in tl_calendar_events
$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventUnsubscribeNotificationSender'] = [
    'exclude'    => true,
    'foreignKey' => 'tl_user.name',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'eval'       => ['mandatory' => false, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50 override_event'],
    'sql'        => "int(10) unsigned NOT NULL default '0'",
];
