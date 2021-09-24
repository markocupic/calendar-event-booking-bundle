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

use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingMemberListModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingUnsubscribeFromEventModuleController;

/**
 * Table tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes'][CalendarEventBookingEventBookingModuleController::TYPE] = '{title_legend},name,headline,type;{form_legend},form;{booking_admin_groups},calendarEventBookingFrontendAdminGroups;{notification_center_legend:hide},enableNotificationCenter;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][CalendarEventBookingUnsubscribeFromEventModuleController::TYPE] = '{title_legend},name,headline,type;{notification_center_legend:hide},unsubscribeFromEventNotificationIds;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][CalendarEventBookingMemberListModuleController::TYPE] = '{title_legend},name,headline,type;{template_legend},calendarEventBookingMemberListPartialTemplate,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// unsubscribeFromEventNotificationIds
$GLOBALS['TL_DCA']['tl_module']['fields']['unsubscribeFromEventNotificationIds'] = array(
	'exclude'    => true,
	'search'     => true,
	'inputType'  => 'select',
	'foreignKey' => 'tl_nc_notification.title',
	'eval'       => array('mandatory' => false, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'),
	'sql'        => "blob NULL",
	'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
);

// Member list partial template
$GLOBALS['TL_DCA']['tl_module']['fields']['calendarEventBookingMemberListPartialTemplate'] = array(
	'exclude'          => true,
	'inputType'        => 'select',
	'options_callback' => array('Markocupic\CalendarEventBookingBundle\Contao\Dca\TlModule', 'getCalendarEventBookingMemberListPartialTemplate'),
	'eval'             => array('tl_class' => 'w50'),
	'sql'              => "varchar(128) NOT NULL default 'calendar_event_booking_member_list_partial'"
);

// Admin groups
$GLOBALS['TL_DCA']['tl_module']['fields']['calendarEventBookingFrontendAdminGroups'] = array(
	'exclude'    => true,
	'inputType'  => 'checkbox',
	'foreignKey' => 'tl_member_group.name',
	'eval'       => array('multiple' => true),
	'sql'        => "blob NULL",
	'relation'   => array('type' => 'hasMany', 'load' => 'lazy')
);
