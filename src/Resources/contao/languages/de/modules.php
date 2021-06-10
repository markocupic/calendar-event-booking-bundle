<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2021 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2021
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingMemberListModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingUnsubscribeFromEventModuleController;

// Frontend modules
$GLOBALS['TL_LANG']['FMD'][CalendarEventBookingEventBookingModuleController::TYPE] = ['Event-Buchungsformular'];
$GLOBALS['TL_LANG']['FMD'][CalendarEventBookingMemberListModuleController::TYPE] = ['Event-Stornierungsformular'];
$GLOBALS['TL_LANG']['FMD'][CalendarEventBookingUnsubscribeFromEventModuleController::TYPE] = ['Listen Sie in Verbindung mit einem Event Reader die angemeldeten Teilnehmer eines Events auf.'];
