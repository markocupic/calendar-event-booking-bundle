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

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingCheckoutController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingFormController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingMemberListController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingMyBookingsController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingOptInController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingUnsubscribeController;

// Palettes
$GLOBALS['TL_DCA']['tl_module']['palettes'][EventBookingFormController::TYPE] = '{title_legend},name,headline,type;{form_legend},form;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][EventBookingUnsubscribeController::TYPE] = '{title_legend},name,headline,type,ceb_addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][EventBookingMemberListController::TYPE] = '{title_legend},name,headline,type.ceb_addImage;{config_legend:hide},ceb_modMemberList_enableBookingStatusFilter,ceb_modMemberList_sorting;{template_legend},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][EventBookingOptInController::TYPE] = '{title_legend},name,headline,type,ceb_addImage;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][EventBookingCheckoutController::TYPE] = '{title_legend},name,headline,type,ceb_modCheckout_handler,ceb_addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][EventBookingMyBookingsController::TYPE] = '{title_legend},name,headline,type,ceb_addImage;{config_legend:hide},ceb_modMyBookings_startTimeFilter,ceb_modMyBookings_sorting;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Subpalettes
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'ceb_modMemberList_enableBookingStatusFilter';

// Selectors
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['ceb_modMemberList_enableBookingStatusFilter'] = 'ceb_modMemberList_bookingStatusFilter';

$GLOBALS['TL_DCA']['tl_module']['fields']['ceb_addImage'] = [
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr cbx m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => ['type' => 'boolean', 'default' => false],
];

// Fields
$GLOBALS['TL_DCA']['tl_module']['fields']['ceb_modMemberList_enableBookingStatusFilter'] = [
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr cbx m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ceb_modMemberList_bookingStatusFilter'] = [
    'eval'      => ['multiple' => true, 'tl_class' => 'w50'],
    'filter'    => true,
    'inputType' => 'checkbox',
    'options'   => ['waitingList::true', 'waitingList::false', 'optIn::true', 'optIn::false', 'temporaryReserved::true', 'temporaryReserved::false', 'expired::true', 'expired::false', 'canceled::true', 'canceled::false', 'paid::true', 'paid::false'],
    'sql'       => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ceb_modMemberList_sorting'] = [
    'eval'      => ['multiple' => true, 'tl_class' => 'w50'],
    'filter'    => true,
    'inputType' => 'checkboxWizard',
    'options'   => ['addedOn::DESC', 'addedOn::ASC', 'lastname::DESC', 'lastname::ASC'],
    'sql'       => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ceb_modMyBookings_startTimeFilter'] = [
    'eval'      => ['multiple' => false, 'tl_class' => 'w50'],
    'filter'    => true,
    'inputType' => 'select',
    'options'   => ['past', 'upcoming', 'all'],
    'sql'       => ['type' => 'string', 'length' => 64, 'default' => 'upcoming'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ceb_modMyBookings_sorting'] = [
    'eval'      => ['multiple' => false, 'tl_class' => 'w50'],
    'filter'    => true,
    'inputType' => 'select',
    'options'   => ['ASC', 'DESC'],
    'sql'       => ['type' => 'string', 'length' => 64, 'default' => 'ASC'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ceb_modCheckout_handler'] = [
    'eval'      => ['mandatory' => true, 'includeBlankOption' => false, 'tl_class' => 'w50'],
    'exclude'   => true,
    'inputType' => 'select',
    'search'    => true,
    'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => 'default'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ceb_addImage'] = [
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr cbx m12'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'sql'       => ['type' => 'boolean', 'default' => false],
];
