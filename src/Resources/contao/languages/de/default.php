<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 *
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

$GLOBALS['TL_LANG']['MSC']['bookings'] = 'Buchungen';

// Form validation
$GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'] = 'Es sind maximal %s Begleitpersonen pro Teilnehmer möglich.';
$GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'] = 'Eine Buchungsanfrage mit der E-Mail-Adresse "%s" ist bereits eingegangen. Der Anmeldevorgang wurde abgebrochen.';
$GLOBALS['TL_LANG']['MSC']['enterPosIntVal'] = 'Bitte geben Sie eine positive natürliche Zahl ein.';
$GLOBALS['TL_LANG']['MSC']['bookingNotYetPossible'] = 'Die Anmeldung für diesen Anlass ist erst ab %s möglich.';
$GLOBALS['TL_LANG']['MSC']['bookingNoLongerPossible'] = 'Die Anmeldefrist für diesen Anlass ist bereits abgelaufen. Es können leider keine Anmeldungen mehr entgegengenommen werden.';
$GLOBALS['TL_LANG']['MSC']['eventFullyBooked'] = 'Der Anlass ist leider bereits ausgebucht.';

// Unsubscribe from event
$GLOBALS['TL_LANG']['MSC']['unsubscribeInfo'] = 'Sie wurden erfolgreich vom Event "%s" abgemeldet.';
$GLOBALS['TL_LANG']['MSC']['unsubscribeConfirm'] = 'Liebe(r) <span class="event-member-name">%s %s</span>{{br}}Möchten Sie sich wirklich vom Event "%s" abmelden?';
$GLOBALS['TL_LANG']['BTN']['slabelUnsubscribeFromEvent'] = 'Von Event abmelden';
$GLOBALS['TL_LANG']['BTN']['slabelCancel'] = 'Abbrechen';

// Member listing
$GLOBALS['TL_LANG']['BTN']['slabel'] = 'Speichern';
$GLOBALS['TL_LANG']['MSC']['noEventRegistrationsFound'] = 'Zu diesem Event wurden keine Anmeldungen gefunden.';

// Errors
$GLOBALS['TL_LANG']['ERR']['unsubscriptionLimitExpired'] = 'Die Abmeldefrist für Event "%s" ist leider abgelaufen.';
$GLOBALS['TL_LANG']['ERR']['eventNotFound'] = 'Ungültiges Token oder zugewiesene Event konnte nicht gefunden werden.';
$GLOBALS['TL_LANG']['ERR']['invalidBookingToken'] = 'Ungültiges Buchungs-Token.';
$GLOBALS['TL_LANG']['ERR']['eventUnsubscriptionNotAllowed'] = 'Die Abmeldung vom Event "%s" ist nicht möglich.';
$GLOBALS['TL_LANG']['ERR']['invalidUnsubscriptionLimit'] = 'Diese Abmeldezeit ist zu spät (siehe Eventstart und -ende).';
$GLOBALS['TL_LANG']['ERR']['conflictingUnsubscribeLimits'] = 'Sie können nicht gleichzeitig eine Abmeldefrist in Tagen und einen Zeitpunkt angeben. Bitte setzen Sie die Abmeldefrist in Tagen auf 0 oder löschen Sie den Zeitpunkt.';
