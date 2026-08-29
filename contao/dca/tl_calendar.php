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
use Doctrine\DBAL\Platforms\MySQLPlatform;

// Selectors
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'allowEventBooking';
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'requireOptIn';

// Subpalettes
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['requireOptIn'] = 'eventBookingOptInPage,optInInvitationNotification,optInSuccessNotification';
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['allowEventBooking'] = 'emailUnique,requireOptIn,eventBookingCheckoutPage,eventBookingCheckoutHandler,eventUnsubscribePage,subscribeNotification,unsubscribeNotification,waitingListAdvancementNotification,paymentSuccessNotification,paymentPendingNotification,paymentFailedNotification';


PaletteManipulator::create()
    ->addLegend('event_booking_legend', 'title_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('allowEventBooking', 'event_booking_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar');

// Fields
$GLOBALS['TL_DCA']['tl_calendar']['fields']['allowEventBooking'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'w50 cbx m12'],
    'sql'       => ['type' => 'boolean', 'default' => true],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['requireOptIn'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'w50 cbx m12'],
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['emailUnique'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr w50 cbx m12'],
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventUnsubscribePage'] = [
    'exclude'    => true,
    'foreignKey' => 'tl_page.title',
    'inputType'  => 'pageTree',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr m12'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventBookingOptInPage'] = [
    'exclude'    => false,
    'foreignKey' => 'tl_page.title',
    'inputType'  => 'pageTree',
    'eval'       => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr m12'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventBookingCheckoutPage'] = [
    'exclude'    => false,
    'foreignKey' => 'tl_page.title',
    'inputType'  => 'pageTree',
    'eval'       => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr m12'],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventBookingCheckoutHandler'] = [
    'exclude'   => true,
    'inputType' => 'select',
    'search'    => true,
    'eval'      => ['mandatory' => true, 'includeBlankOption' => false, 'tl_class' => 'w50'],
    'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => 'default'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['waitingListAdvancementNotification'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['subscribeNotification'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['paymentSuccessNotification'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

// Sent when a payment has been authorized but is not settled yet, e.g., with
// SEPA direct debit, where the bank confirms days later.
$GLOBALS['TL_DCA']['tl_calendar']['fields']['paymentPendingNotification'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

// Sent when a payment that had already been authorized is rejected afterwards,
// e.g., an unpaid SEPA direct debit.
$GLOBALS['TL_DCA']['tl_calendar']['fields']['paymentFailedNotification'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['unsubscribeNotification'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['optInInvitationNotification'] = [
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['optInSuccessNotification'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];
