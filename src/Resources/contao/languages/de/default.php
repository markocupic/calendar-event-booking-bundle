<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2021 Marko Cupic
 *
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2021
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;

$GLOBALS['TL_LANG']['MSC']['bookings'] = 'Buchungen';

// Form validation
$GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'] = 'Es sind maximal %s Begleitpersonen pro Teilnehmer möglich.';
$GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'] = 'Eine Buchungsanfrage mit der E-Mail-Adresse "%s" ist bereits eingegangen. Der Anmeldevorgang wurde abgebrochen.';
$GLOBALS['TL_LANG']['MSC']['enterPosIntVal'] = 'Bitte geben Sie eine positive natürliche Zahl ein.';
$GLOBALS['TL_LANG']['MSC']['maxMemberLimitExceeded'] = 'Die Gesamtzahl von %s Teilnehmern wird überschritten. Bitte überprüfen Sie die Anzahl der Begleitpersonen.';
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NOT_YET_POSSIBLE] = 'Die Anmeldung für diesen Anlass ist erst ab %s möglich.';
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NO_LONGER_POSSIBLE] = 'Die Anmeldefrist für diesen Anlass ist bereits abgelaufen. Es können leider keine Anmeldungen mehr entgegengenommen werden.';
$GLOBALS['TL_LANG']['MSC'][CalendarEventBookingEventBookingModuleController::CASE_EVENT_FULLY_BOOKED] = 'Der Anlass ist leider bereits ausgebucht.';

// Form validation backend
$GLOBALS['TL_LANG']['MSC']['adjustedBookingPeriodEndtime'] = 'Das Event-Enddatum für den Buchungszeitraum wurde angepasst.';

// Unsubscribe from event
$GLOBALS['TL_LANG']['MSC']['unsubscribeInfo'] = 'Ihre Anmeldung zu Event "%s" wurde erfolgreich storniert.';
$GLOBALS['TL_LANG']['MSC']['unsubscribeConfirm'] = 'Liebe(r) <span class="event-member-name">%s %s</span>{{br}}Möchten Sie Ihre Anmeldung zu Event "%s" stornieren?';
$GLOBALS['TL_LANG']['BTN']['slabelUnsubscribeFromEvent'] = 'Anmeldung stornieren';
$GLOBALS['TL_LANG']['BTN']['slabelCancel'] = 'Abbrechen';

// Errors
$GLOBALS['TL_LANG']['ERR']['unsubscriptionLimitExpired'] = 'Die Stornierungsfrist für Event "%s" ist leider abgelaufen.';
$GLOBALS['TL_LANG']['ERR']['eventNotFound'] = 'Ungültiges Token oder zugewiesene Event konnte nicht gefunden werden.';
$GLOBALS['TL_LANG']['ERR']['invalidBookingToken'] = 'Ungültiges Buchungs-Token.';
$GLOBALS['TL_LANG']['ERR']['eventUnsubscriptionNotAllowed'] = 'Die Abmeldung vom Event "%s" ist nicht möglich.';
$GLOBALS['TL_LANG']['ERR']['invalidUnsubscriptionLimit'] = 'Diese Abmeldezeit liegt zu weit in der Zukunft (siehe Eventstart und -ende).';
$GLOBALS['TL_LANG']['ERR']['conflictingUnsubscribeLimits'] = 'Sie können nicht gleichzeitig eine Stornierungsfrist in Tagen und einen Zeitpunkt angeben. Bitte setzen Sie die Stornierungsfrist in Tagen auf 0 oder löschen Sie den Zeitpunkt.';
