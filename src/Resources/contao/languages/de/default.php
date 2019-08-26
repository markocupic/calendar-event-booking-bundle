<?php

/**
 * @copyright  Marko Cupic 2019
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */


$GLOBALS['TL_LANG']['MSC']['bookings'] = 'Buchungen';

// Form validation
$GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'] = 'Es sind maximal %s Begleitpersonen pro Teilnehmer möglich.';
$GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'] = 'Eine Buchungsanfrage mit der E-Mail-Adresse "%s" ist bereits eingegangen. Der Anmeldevorgang wurde abgebrochen.';
$GLOBALS['TL_LANG']['MSC']['enterPosIntVal'] = 'Bitte geben Sie eine positive natürliche Zahl ein.';

// Unsubscribe from event
$GLOBALS['TL_LANG']['BTN']['slabelUnsubscribeFromEvent'] = 'Von Event abmelden';
$GLOBALS['TL_LANG']['BTN']['slabelCancel'] = 'Abbrechen';

// Errors
$GLOBALS['TL_LANG']['ERR']['unsubscriptionLimitExpired'] = 'Die Abmeldefrist für Event "%s" ist leider abgelaufen.';
$GLOBALS['TL_LANG']['ERR']['eventNotFound'] = 'Ungültiges Token oder zugewiesene Event konnte nicht gefunden werden.';
$GLOBALS['TL_LANG']['ERR']['invalidBookingToken'] = 'Ungültiges Buchungs-Token.';
