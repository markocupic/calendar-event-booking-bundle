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

// Palettes
PaletteManipulator::create()
    ->addLegend('event_booking_legend', 'title_legend', PaletteManipulator::POSITION_AFTER)
    ->addLegend('event_booking_notification_legend', 'event_booking_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['emailUnique', 'requireOptIn', 'eventBookingCheckoutPage', 'eventBookingCheckoutHandler', 'eventUnsubscribePage'], 'event_booking_legend', PaletteManipulator::POSITION_APPEND)
    ->addField(['subscribeNotification', 'unsubscribeNotification', 'waitingListAdvancementNotification', 'paymentSuccessNotification'], 'event_booking_notification_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar');

$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'requireOptIn';

// Subpalettes
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['requireOptIn'] = 'eventBookingOptInPage,optInInvitationNotification,optInSuccessNotification';

// Fields
$GLOBALS['TL_DCA']['tl_calendar']['fields']['emailUnique'] = [
    'eval'      => ['tl_class' => 'w50 cbx m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['requireOptIn'] = [
    'inputType' => 'checkbox',
    'exclude'   => true,
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'w50 cbx m12'],
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventUnsubscribePage'] = [
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['mandatory' => false, 'fieldType' => 'radio', 'tl_class' => 'clr m12'],
    'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventBookingOptInPage'] = [
    'exclude'    => false,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr m12'],
    'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventBookingCheckoutPage'] = [
    'exclude'    => false,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr m12'],
    'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['eventBookingCheckoutHandler'] = [
    'eval'      => ['mandatory' => true, 'includeBlankOption' => false, 'tl_class' => 'w50'],
    'exclude'   => true,
    'inputType' => 'select',
    'search'    => true,
    'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => 'default'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['waitingListAdvancementNotification'] = [
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['subscribeNotification'] = [
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['paymentSuccessNotification'] = [
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['unsubscribeNotification'] = [
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
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
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'sql'       => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];
