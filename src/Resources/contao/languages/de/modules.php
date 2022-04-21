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

// Frontend modules
$GLOBALS['TL_LANG']['FMD'][CalendarEventBookingEventBookingModuleController::TYPE] = ['Event-Buchungsformular'];
$GLOBALS['TL_LANG']['FMD'][CalendarEventBookingMemberListModuleController::TYPE] = ['Event-Teilnehmer-Liste'];
$GLOBALS['TL_LANG']['FMD'][CalendarEventBookingUnsubscribeFromEventModuleController::TYPE] = ['Event-Stornierungsformular'];
