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

use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingMemberListModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingUnsubscribeFromEventModuleController;

// Palettes
$GLOBALS['TL_DCA']['tl_module']['palettes'][CalendarEventBookingEventBookingModuleController::TYPE] = '{title_legend},name,headline,type;{form_legend},form;{notification_legend:hide},activateNotification;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][CalendarEventBookingUnsubscribeFromEventModuleController::TYPE] = '{title_legend},name,headline,type;{notification_legend:hide},cebb_unsubscribeNotification;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][CalendarEventBookingMemberListModuleController::TYPE] = '{title_legend},name,headline,type;{template_legend},cebb_memberListPartialTemplate,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Fields
$GLOBALS['TL_DCA']['tl_module']['fields']['cebb_unsubscribeNotification'] = [
    'eval'       => ['mandatory' => false, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'],
    'exclude'    => true,
    'foreignKey' => 'tl_nc_notification.title',
    'inputType'  => 'select',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    'search'     => true,
    'sql'        => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cebb_memberListPartialTemplate'] = [
    'eval'      => ['tl_class' => 'w50'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => "varchar(128) NOT NULL default 'calendar_event_booking_member_list_partial'",
];
