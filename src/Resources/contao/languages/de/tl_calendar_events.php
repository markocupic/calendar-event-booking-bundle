<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2021 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2021
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

// Operations
$GLOBALS['TL_LANG']['tl_calendar_events']['registrations'] = 'Teilnehmer des Events mit ID %s anzeigen';

// Legends
$GLOBALS['TL_LANG']['tl_calendar_events']['booking_options_legend'] = 'Buchungseinstellungen';
$GLOBALS['TL_LANG']['tl_calendar_events']['notification_center_legend'] = 'Benachrichtigungs-Einstellungen';
$GLOBALS['TL_LANG']['tl_calendar_events']['event_unsubscribe_legend'] = 'Stornierungs-Einstellungen';

// Fields
$GLOBALS['TL_LANG']['tl_calendar_events']['street'] = ['Strasse', 'Geben Sie bitte eine Strasse ein.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['postal'] = ['PLZ', 'Geben Sie bitte eine Postleitzahl ein.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['city'] = ['Ort', 'Geben Sie bitte einen Ort ein.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['addBookingForm'] = ['Buchungsformular aktivieren', 'Aktivieren Sie das Buchungsformular und legen Sie die Optionen fest.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['maxEscortsPerMember'] = ['Maximale Anzahl Begleitpersonen pro Teilnehmer', 'Legen Sie die maximal mögliche Anzahl an Begleitpersonen pro Teilnehmer fest.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['includeEscortsWhenCalculatingRegCount'] = ['Begleitpersonen zur totalen Teilnehmerzahl hinzufügen', 'Legen Sie fest, ob die Begleitpersonen zur totalen Teilnehmerzahl dazugerechnet werden sollen.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['bookingStartDate'] = ['Buchung möglich ab:', 'Legen Sie fest, ab welchem Zeitpunkt eine Buchung möglich ist.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['bookingEndDate'] = ['Buchung möglich bis:', 'Legen Sie fest, bis zu welchem Zeitpunkt eine Buchung möglich ist.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['minMembers'] = ['Minimale Teilnehmerzahl', 'Geben Sie bitte die minimale Teilnehmerzahl ein.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['maxMembers'] = ['Maximale Teilnehmerzahl', 'Geben Sie bitte die maximale Teilnehmerzahl ein.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['enableNotificationCenter'] = ['Nachricht für die Buchungsbestätigung auswählen', 'Wählen Sie eine Nachricht (Notification Center) für die Buchungsbestätigung aus.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['eventBookingNotificationCenterIds'] = ['Eine oder mehrere Benachrichtigungen auswählen', 'Wählen Sie hier eine oder mehrere Benachrichtigungen aus.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['eventBookingNotificationSender'] = ['Absender der Buchungsbestätigung auswählen', 'Wählen Sie hier einen Absender für die Benachrichtigung aus.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['enableDeregistration'] = ['Abmeldung von Event erlauben', 'Wählen Sie hier, ob eine Event-Abmeldung erlaubt werden soll.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['eventUnsubscribePage'] = ['Seite mit dem Event-Stornierungsformular-Modul wählen', 'Wählen Sie die Seite mit dem Event-Stornierungsformular-Modul aus.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['unsubscribeLimit'] = ['Abmeldefrist in Tagen', 'Geben Sie an wie viele Tage bis zum Event-Anfang Abmeldungen ermöglicht werden sollen.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['unsubscribeLimitTstamp'] = ['Abmeldefrist-Zeitpunkt', 'Geben Sie einen genauen Zeitpunkt an, bis zu dem die Abmeldung möglich sein soll. Überschreibt die Abmeldefrist in Tagen.'];
$GLOBALS['TL_LANG']['tl_calendar_events']['enableMultiBookingWithSameAddress'] = ['Mehrfachbuchung mit gleicher E-Mailadresse ermöglichen', 'Geben Sie an, ob Merhfachbuchung mit einer bereits verwendeten E-Mail-Adresse möglich sein sollen.'];

