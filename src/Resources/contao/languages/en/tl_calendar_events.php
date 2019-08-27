<?php

/**
 * @copyright  Marko Cupic 2019
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */

// Legends
$GLOBALS['TL_LANG']['tl_calendar_events']['booking_options_legend'] = "Buchungseinstellungen";
$GLOBALS['TL_LANG']['tl_calendar_events']['notification_center_legend'] = "Benachrichtigungs-Einstellungen";
$GLOBALS['TL_LANG']['tl_calendar_events']['event_unsubscribe_legend'] = "Event unsubscription settings";


// Fields
$GLOBALS['TL_LANG']['tl_calendar_events']['street'] = array("Strasse", "Geben Sie bitte eine Strasse ein.");
$GLOBALS['TL_LANG']['tl_calendar_events']['postal'] = array("PLZ", "Geben Sie bitte eine Postleitzahl ein.");
$GLOBALS['TL_LANG']['tl_calendar_events']['city'] = array("Ort", "Geben Sie bitte einen Ort ein.");
$GLOBALS['TL_LANG']['tl_calendar_events']['addBookingForm'] = array("Buchungsoptionen öffnen", 'Öffnen Sie die Buchungsoptionen.');
$GLOBALS['TL_LANG']['tl_calendar_events']['maxEscortsPerMember'] = array("Maximale Anzahl Begleitpersonen pro Teilnehmer", "Legen Sie die maximal mögliche Anzahl an Begleitpersonen pro Teilnehmer fest.");
$GLOBALS['TL_LANG']['tl_calendar_events']['bookingStartDate'] = array("Buchung möglich ab:", "Legen Sie fest, ab welchem Zeitpunkt eine Buchung möglich ist.");
$GLOBALS['TL_LANG']['tl_calendar_events']['bookingEndDate'] = array("Buchung möglich bis:", "Legen Sie fest, bis zu welchem Zeitpunkt eine Buchung möglich ist.");
$GLOBALS['TL_LANG']['tl_calendar_events']['emailFromName'] = array("E-Mail Absendernamen", "Geben Sie bitte den Absendernamen der Bestätigungs E-Mail ein.");
$GLOBALS['TL_LANG']['tl_calendar_events']['emailFrom'] = array("E-Mail Adresse des Absenders", "Geben Sie bitte die Absender E-Mail-Adresse an. An diese Adresse werden allfällige Rückfragen gesendet.");
$GLOBALS['TL_LANG']['tl_calendar_events']['bookingConfirmationEmailBody'] = array("E-Mail-Text für Anmeldebestätigung an Teilnehmer", "Personalisieren Sie die E-Mail mit Tags: ##firstname## ##eventname## ##gender## ##firstname## ##lastname## ##street## ##postal## ##city## ##phone## ##email## ##escorts##");
$GLOBALS['TL_LANG']['tl_calendar_events']['minMembers'] = array("Minimale Teilnehmerzahl", "Geben Sie bitte eine Teilnehmerzahl ein.");
$GLOBALS['TL_LANG']['tl_calendar_events']['maxMembers'] = array("Maximale Teilnehmerzahl", "Geben Sie bitte eine Teilnehmerzahl ein.");
$GLOBALS['TL_LANG']['tl_calendar_events']['enableNotificationCenter'] = array('Notification Center f&uuml;r die Anmeldebest&auml;tigung nutzen','Notification Center zur Anmeldebst&auml;tigung nutzen.');
$GLOBALS['TL_LANG']['tl_calendar_events']['eventBookingNotificationCenterIds'] = array('Eine oder mehrere Benachrichtigungen ausw&auml;hlen','W&auml;hlen Sie hier eine oder mehrere Benachrichtigungen aus.');
$GLOBALS['TL_LANG']['tl_calendar_events']['eventBookingNotificationSender'] = array('Absender der Buchungsbest&auml;tigung ausw&auml;hlen','W&auml;hlen Sie hier einen Absender f&uuml;r die Benachrichtigung aus.');
$GLOBALS['TL_LANG']['tl_calendar_events']['enableDeregistration'] = array('Enable event unsubscription', 'Please choose if event unsubscription should be allowed.');
$GLOBALS['TL_LANG']['tl_calendar_events']['unsubscribeLimit'] = array('Unsubscription limit in days', 'Please set the number of days you allow users to unsubscribe from day of the event.');
