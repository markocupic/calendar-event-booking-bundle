<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 *
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

/**
 * Table tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['calendar_event_booking_event_booking_module'] = '{title_legend},name,headline,type;{form_legend},form;{notification_center_legend:hide},enableNotificationCenter;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['calendar_event_booking_unsubscribe_from_event_module'] = '{title_legend},name,headline,type;{notification_center_legend:hide},unsubscribeFromEventNotificationIds;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['calendar_event_booking_member_list_module'] = '{title_legend},name,headline,type;{template_legend},calendar_event_booking_member_list_partial_template,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// unsubscribeFromEventNotificationIds
$GLOBALS['TL_DCA']['tl_module']['fields']['unsubscribeFromEventNotificationIds'] = [
    'exclude'    => true,
    'search'     => true,
    'inputType'  => 'select',
    'foreignKey' => 'tl_nc_notification.title',
    'eval'       => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'],
    'sql'        => "blob NULL",
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
];

// Member list partial template
$GLOBALS['TL_DCA']['tl_module']['fields']['calendar_event_booking_member_list_partial_template'] = [
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => ['Markocupic\CalendarEventBookingBundle\Contao\Dca\TlModule', 'getCalendarEventBookingMemberListPartialTemplate'],
    'eval'             => ['tl_class' => 'w50'],
    'sql'              => "varchar(128) NOT NULL default 'calendar_event_booking_member_list_partial'"
];

