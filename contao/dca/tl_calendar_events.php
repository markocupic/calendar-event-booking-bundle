<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook\PriceRegexpListener;

// Table config
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['doNotCopyRecords'] = true;
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['ctable'][] = 'tl_calendar_events_member';

// Palettes
PaletteManipulator::create()
    ->addLegend('booking_options_legend', 'details_legend', PaletteManipulator::POSITION_AFTER)
    ->addLegend('cost_legend', 'details_legend', PaletteManipulator::POSITION_AFTER)
    ->addLegend('notification_center_legend', 'booking_options_legend', PaletteManipulator::POSITION_AFTER)
    ->addLegend('event_unsubscribe_legend', 'notification_center_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['enableBookingForm'], 'booking_options_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['netPrice', 'currencyCode', 'taxValue'], 'cost_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['eventBookingNotificationSender'], 'notification_center_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['enableDeregistration'], 'event_unsubscribe_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['street', 'postal', 'city'], 'location', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_calendar_events');

// Selector
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'enableBookingForm';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'enableDeregistration';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'enableWaitingList';

// Subpalettes
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['enableBookingForm'] = 'bookingStartDate,bookingEndDate,minBookings,maxBookings,maxTicketsPerBooking,maxEscortsPerBooking,enableWaitingList';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['enableDeregistration'] = 'unsubscribeLimit,unsubscribeLimitTstamp';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['enableWaitingList'] = 'maxWaitingList';

// Operations
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations']['bookings'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['bookings'],
    'href'  => 'do=calendar&table=tl_calendar_events_member',
    'icon'  => 'bundles/markocupiccalendareventbooking/icons/users.svg',
];

// Fields
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['street'] = [
    'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
    'exclude'   => true,
    'flag'      => DataContainer::SORT_INITIAL_LETTER_ASC,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['postal'] = [
    'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => ['type' => 'string', 'length' => 32, 'notnull' => true, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['city'] = [
    'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
    'exclude'   => true,
    'flag'      => DataContainer::SORT_INITIAL_LETTER_ASC,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['currencyCode'] = [
    'eval'      => ['mandatory' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'options'   => ['CHF', 'EUR', 'GBP', 'USD'],
    'sql'       => ['type' => 'string', 'length' => 3, 'default' => 'EUR'],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['netPrice'] = [
    'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => PriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    // 'sql'       => ['type' => 'string', 'length' =>
    // MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
    'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL DEFAULT 0',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['taxValue'] = [
    'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => PriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL DEFAULT 0',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableBookingForm'] = [
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr cbx m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingStartDate'] = [
    'default'   => null,
    'eval'      => ['rgxp' => 'datim', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'clr w50 wizard'],
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingEndDate'] = [
    'default'   => null,
    'eval'      => ['rgxp' => 'datim', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['minBookings'] = [
    'default'   => 0,
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'natural', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => ['type' => 'integer', 'length' => 5, 'unsigned' => true, 'notnull' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxBookings'] = [
    'default'   => 0,
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'natural', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => ['type' => 'integer', 'length' => 5, 'unsigned' => true, 'notnull' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxTicketsPerBooking'] = [
    'default'   => 0,
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'natural', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => ['type' => 'integer', 'length' => 5, 'unsigned' => true, 'notnull' => true, 'default' => 1],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxEscortsPerBooking'] = [
    'default'   => 0,
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'natural', 'mandatory' => true],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => ['type' => 'integer', 'length' => 5, 'unsigned' => true, 'notnull' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableWaitingList'] = [
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr cbx m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxWaitingList'] = [
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'natural'],
    'exclude'   => true,
    'inputType' => 'text',
    'search'    => true,
    'sorting'   => true,
    'sql'       => ['type' => 'integer', 'length' => 5, 'unsigned' => true, 'notnull' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationSender'] = [
    'default'    => 0,
    'eval'       => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'exclude'    => true,
    'foreignKey' => 'tl_user.name',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableDeregistration'] = [
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr cbx m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimit'] = [
    'default'   => 0,
    'eval'      => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'options'   => range(0, 720),
    'sorting'   => true,
    'sql'       => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimitTstamp'] = [
    'default'   => null,
    'eval'      => ['rgxp' => 'datim', 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
    'exclude'   => true,
    'inputType' => 'text',
    'sorting'   => true,
    'sql'       => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => false],
];
