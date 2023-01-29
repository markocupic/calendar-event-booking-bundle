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

use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingMemberListModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingUnsubscribeFromEventModuleController;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;

// Palettes
$GLOBALS['TL_DCA']['tl_module']['palettes'][CalendarEventBookingEventBookingModuleController::TYPE] = '{title_legend},name,headline,type;{form_legend},form;{notification_legend:hide},activateBookingNotification;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][CalendarEventBookingUnsubscribeFromEventModuleController::TYPE] = '{title_legend},name,headline,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][CalendarEventBookingMemberListModuleController::TYPE] = '{title_legend},name,headline,type;{config_legend},cebb_memberListAllowedBookingStates;{template_legend},cebb_memberListPartialTemplate,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Fields
$GLOBALS['TL_DCA']['tl_module']['fields']['cebb_memberListPartialTemplate'] = [
    'eval'      => ['tl_class' => 'w50'],
    'exclude'   => true,
    'inputType' => 'select',
    'sql'       => "varchar(128) NOT NULL default 'calendar_event_booking_member_list_partial'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cebb_memberListAllowedBookingStates'] = [
    'eval'      => ['multiple' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'options'   => [
        BookingState::STATE_NOT_CONFIRMED,
        BookingState::STATE_CONFIRMED,
        BookingState::STATE_REJECTED,
        BookingState::STATE_WAITING_LIST,
        BookingState::STATE_UNSUBSCRIBED,
        BookingState::STATE_UNDEFINED,
    ],
    'sql'       => "varchar(512) NOT NULL default 'a:1:{i:0;s:".strlen(BookingState::STATE_CONFIRMED).":\"".BookingState::STATE_CONFIRMED."\";}'",
];
