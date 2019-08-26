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
$GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'] = 'Eine Anmeldung mit der E-Mail-Adresse "%s" ist bereits eingegangen. Der Anmeldevorgang wurde abgebrochen.';
$GLOBALS['TL_LANG']['MSC']['enterPosIntVal'] = 'Bitte geben Sie eine positive natürliche Zahl ein.';

// Unsubscribe from event
$GLOBALS['TL_LANG']['BTN']['slabelUnsubscribeFromEvent'] = 'Unsubscribe from event';
$GLOBALS['TL_LANG']['BTN']['slabelCancel'] = 'Cancel';

// Errors
$GLOBALS['TL_LANG']['ERR']['unsubscriptionLimitExpired'] = 'The unsubscription limit for event "%s" has expired.';
$GLOBALS['TL_LANG']['ERR']['eventNotFound'] = 'Invalid booking token or could not find assigned event.';
$GLOBALS['TL_LANG']['ERR']['invalidBookingToken'] = 'Invalid booking token.';
