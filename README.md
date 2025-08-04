# Events buchen mit Contao

### Achtung: Bei der Migration von Version 5.x nach 6.x kam es zu vielen [Änderungen](https://github.com/markocupic/calendar-event-booking-bundle/blob/6.x/UPGRADE.md). Die Event- und Kalendereinstellungen müssen nach der Migration unbedingt überprüft und angepasst werden. Vor dem Upgrade sollte ein Datenbank-Backup erstellt werden.

## Events buchen

Mit dieser Erweiterung für Contao CMS werden Events über ein Anmeldeformular buchbar.
Das Anmeldeformular kann im Contao Formulargenerator erstellt werden. Während des Installationsprozesses wird ein Sample Anmeldeformular generiert.
Beim Absenden des Formulars werden die Werte in der Datenbank in der Tabelle tl_calendar_events_member abgelegt. Die Buchungen sind im Backend einsehbar und über eine CSV-Datei exportierbar.
Optional ist eine Buchung auf Warteliste möglich. Die Personen auf der Warteliste rücken automatisch nach, wenn Plätze durch Stornierung frei werden.

## Bezahlfunktion

Die Bezahlfunktion ist zahlungspflichtig (Bitte Autor der Extension per E-Mail kontaktieren)

Im Moment sind folgende **Zahlungsmethoden** vorhanden:

- PayPal

Event-Organisator und Teilnehmer können bei jedem Prozess automatisch benachrichtigt werden (Notification Cecnter).

## Warteliste

Es kann eine Warteliste aktiviert werden. Und Personen rücken automatisch nach, wenn Plätze durch Stornierung frei werden. Die Warteliste sollte nicht mit einem Bezahlungs-Checkout verbunden werden.

## Double-Opt-In

Bei den Buchungseinstellungen kann optional eine Bestätigung der Buchungsanfrage aktiviert werden. Dabei wird mit der Benachrichtigung (Event Buchung: Benachrichtigung nach dem Absenden des Event-Buchungs-Formulars) ein Link versandt. Dazu muss das Modul "Event Buchung: Benachrichtigung nach der
Bestätigung der Buchung mit Link" erstellt werden.
Wenn der Kunde/User seine Buchungsanfrage nicht bestätigt, wird nach einer konfigurierbaren Zeit seine Anfrage abgelehnt und sein Platz wieder für andere frei.
Abschnitt "Konfiguration" beachten!

## Frontend-Module

| Frontend-Modul                                    | Erklärung                                                                                                                                                                                                                                                                                                                                                                                                                                         |
|---------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Event-Buchungsformular                            | Wird benötigt, um das Event-Buchungsformular auszugeben. Das Modul ist auf den Event-Identifier in der URL angewiesen und befindet sich typischerweise auf der selben Seite wie das Event-Leser-Modul.                                                                                                                                                                                                                                            |
| Event-Buchungs-Checkout (Zusammenfassung/Zahlung) | Dieses Modul sollte auf der Weiterleitungsseite eingerichtet werden, auf die Kunden nach dem Absenden des Buchungsformulars geleitet werden. Es zeigt eine kurze Bestätigung der Buchung an. Oder löst den Zahlungscheckout aus (kostenpflichtig).                                                                                                                                                                                                |                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| Event-Buchungs-Bestätigung (Double-Opt-In)        | Optional! Dieses Modul muss auf der Seite platziert werden, wohin User geleitet werden, wenn sie den Buchungsbestätigungslink angeklickt haben, welcher mit der Benachrichtigung (Event Buchung: Benachrichtigung nach dem Absenden des Event-Buchungs-Formulars) versandt worden ist. Die Seite muss in den Kalendereinstellungen konfiguriert werden. Dieses Modul sollte nicht in Zusammenhang mit einem Bezahlungs-Checkout berwendet werden. |
| Event-Stornierungsformular                        | Optional! Dieses Modul muss auf der Seite platziert werden, wohin User geleitet werden, wenn sie den Buchungs-Stornierungslink angeklickt haben, welcher mit der Benachrichtigung (Event Buchung: Benachrichtigung nach dem Absenden des Event-Buchungs-Formulars) versandt worden ist. Die Seite muss in den Kalendereinstellungen konfiguriert werden.                                                                                          |
| Event-Teilnehmer-Liste                            | Optional! Dieses Modul listet die vorhandenen Buchungen auf. Das Modul ist auf den Event-Identifier in der URL angewiesen und befindet sich typischerweise auf der selben Seite wie das Event-Leser-Modul.                                                                                                                                                                                                                                        |

## Einrichtung (Ablauf)

### 1. Kalender und Events anlegen.

### 2. Buchungsformular erstellen und erweitern

Beim Aufrufen der Datenbankmigration wird **automatisch** ein Beispielformular mit allen benötigten Feldern generiert.

- **Wichtig!!! Im Formular muss die Checkbox "Aktiviere Event-Buchungsformular-Funktion" aktiviert sein.**
- Zudem kann optional die Weiterleitungsseite ausgewählt werden.
- **Bei der Benutzung eines Zahlungscheckouts sollte keine Weiterleitungsseite eingerichtet werden!**
- Weitere Einstellungen müssen keine zwingend gemacht werden. Es sollte keine Benachrichtigung ausgewählt werden. Diese wird beim Event ausgewählt.
- Folgende Felder werden im Beispielformular mitgeliefert und deren Inhalt beim Absenden des Formulars wird in der Datenbank (tl_calendar_events_member) gespeichert:
  `waitingList`, `gender`, `firstname`, `lastname`, `dateOfBirth`, `street`, `postal`, `city`, `phone`, `email`, `ticketAmount`, `escorts`, `notes`
- Benutzen Sie das Feld `ticketAmount`, wenn für jedes Ticket ein Platz von der Gesamtzahl der maximal möglichen Teilnehmerzahl abgezogen werden soll.
- Benutzen Sie das Feld `escorts`, wenn es Begleitpersonen gibt. Begleitpersonen werden **nicht** zur Gesamtzahl der Teilnehmerzahl dazugezählt.
- Es können zusätzliche Felder im Formulargenerator erstellt werden. Damit die Daten in der Datenbank gespeichert werden, muss die DCA im Projekt-ROOT unter `contao/dca/tl_calendar_events_member.php` erweitert werden. Danach muss via Shell der Cache neu aufgebaut `composer install` und die
  Datenbankmigration ausgeführt werden. `vendor/bin/contao-console contao:migrate`

```php
<?php
// Put this in TL_ROOT/contao/dca/tl_calendar_events_member.php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Add an additional field to tl_calendar_events_member
$GLOBALS['TL_DCA']['tl_calendar_events_member']['fields']['foodHabilities'] = [
    'exclude'   => true,
    'search'    => true,
    'sorting'   => true,
    'inputType' => 'select',
    'options'   => ['vegetarian', 'vegan'],
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => ['type' => 'string', 'length' => 255, 'notnull' => true],
];

// Add a new legend and custom field to the default.
PaletteManipulator::create()
    ->addLegend('food_legend', 'personal_legend', PaletteManipulator::POSITION_AFTER)
    ->addField(['foodHabilities'], 'food_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar_events_member');
```

### 3. Frontend Module Event-Buchungsformular und Event-Buchungs-Checkout (Zusammenfassung/Zahlung) anlegen

### 4. Seite und Artikel anlegen

- Seite und Artikel mit dem Module **Buchungsformular** anlegen. Das Modul **Buchungsformular** ist auf den Event-Alias in der URL angewiesen und sollte idealerweise auf einer Event-Detail-Seite angelegt werden.

- Seite und Artikel mit dem Modul **Event-Buchungs-Checkout** einrichten.

### 5. Benachrichtigungen mit Notification Center anlegen

| Benachrichtigungen (Notification Center)                                                  |
|-------------------------------------------------------------------------------------------|
| Event Buchung: Benachrichtigung nach dem Absenden des Event-Buchungs-Formulars            |
| Event Buchung: Benachrichtigung nach der Bestätigung der Buchung mit Link (Double-Opt-In) |
| Event Buchung: Benachrichtigung nach der Event-Stornierung                                |
| Event Buchung: Benachrichtigung nach dem Nachrücken von der Warteliste                    |
| Event Buchung: Benachrichtigung nach erfolgreicher Zahlung                                |

Versenden Sie zu versch. Zeitpunkten Benachrichtigungen und nutzen Sie dabei die **Simple Tokens**.

Mit `##member_unsubscribeLink##` kann ein tokengesicherter Event-Stornierungs-Link mitgesandt werden.
Dazu muss aber im Event die Event-Stornierung aktiviert werden und im Kalender die Seite mit dem Modul **Event-Stornierungsformular** eingerichtet worden sein.

#### Gebrauch der Simple Tokens im Notification Center

|                                           |                              |                                                                                                                                                                                                                                                           |
|-------------------------------------------|------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Teilnehmer                                | `tl_calendar_events_member`  | `##member_gender##` (Männlich, Weiblich oder Divers), `##member_salutation##` (Übersetzt: Herr oder Frau), `##member_email##`, `##member_firstname##`, `##member_street##`, etc.                                                                          |
| Event                                     | `tl_calendar_events`         | `##event_title##`, `##event_street##`, `##event_postal##`, `##event_city##`, `##event_unsubscribeLimitTstamp##`, etc.                                                                                                                                     |
| Organisator/Email-Absender                | `tl_user`                    | `##organizer_name##`, `##organizer_email##`, etc.                                                                                                                                                                                                         |
| Angaben zur Zahlung                       | `tl_calendar_events_payment` | `##uuid##`, `##bookingUuid##`, `##paidAt##`, `##refundedAt##`, `##method##`, `##transactionId##`, `##transactionStatus##`, `##currencyCode##`, `##taxValue##`, `##grossAmount##`, `##netAmount##`, `##vatAmount##`, `##transactionDetails##`, `##notes##` |
| Insert-Tags und Simple Tokens kombinieren | `format_date`, usw.          | Simple Tokens lassen sich mit Insert-Tags kombinieren. -> `{{format_date::##member_dateOfBirth##::d.m.Y}}`, `{{format_date::##event_startDate##::d.m.Y}}`, usw.                                                                                           |

#### Benachrichtigung (Beispiel: Benachrichtigung nach erfolgreicher Zahlung)

```
{if member_gender=='male'}
Sehr geehrter Herr ##member_firstname ##` ##member_lastname##
{elseif member_gender=='female'}
Sehr geehrte Frau ##member_firstname## ##member_lastname##
{else}
Hallo ##member_firstname## ##member_lastname##
{endif}

Hiermit bestätigen wir den Eingang Ihrer Buchungsanfrage zur Veranstaltung "##event_title##" vom {{format_date::##event_startDate##::d.m.Y}}.

Ihre Angaben:
Name/Vorname: ##member_firstname## ##member_lastname##
Adresse: ##member_street##, ##member_postal##, ##member_city##
Telefon: ##member_phone##
E-Mail: ##member_email##
Begleitpersonen: ##member_escorts##
Anzahl Tickets: ##member_ticketAmount##
Geschlecht: ##member_gender##
Geburtsdatum: {{format_date::##member_dateOfBirth##::d.m.Y}}

Stornierung erlauben: {if event_enableDeregistration=='1'}Ja{else}Nein{endif}

{if payment_method=='paypal'}
Ihre Bezahlung:
Bezahlanbieter: ##payment_method##
Total: ##payment_grossAmount## ##payment_currencyCode##
{endif}

{if member_waitingList=='1'}
Auf Warteliste: JA!
{endif}

{if calendar_requireOptIn=='1'}
Bitte beachten Sie, dass Ihre Buchung erst nach Bestätigung mit dem Berstätigungslink gültig wird.
##member_optInLink##
{endif}

{if event_enableDeregistration=='1'}
Bitte benutzen Sie folgenden Link, um sich wieder von der Veranstaltung abzumelden:
##member_unsubscribeLink##
Achtung! es können nur Stornierungen bis zum {{format_date::##event_unsubscribeLimitTstamp##::d.m.}} angenommen werden.
{endif}

Freundliche Grüsse

##organizer_name##
```

### 7. In den Kalendereinstellungen alle Weiterleitungsseiten einrichten.

### 8. In den Kalendereinstellungen alle gewünschten Benachrichtigungen auswählen.

## Template Variablen

Folgende zusätzliche Template Variablen sind in allen Kalender-Templates einsetzbar:

| Tag               | Type   | Erklärung                                                                                                                                  |
|-------------------|--------|--------------------------------------------------------------------------------------------------------------------------------------------|
| `event`           | object | `\Contao\CalendarEvntsModel $event` Objekt mit allen Angaben zum Event. Z.B. gibt `event.title` den Event-Namen aus.                       |
| `calendar`        | object | `\Contao\CalendarModel $calendar` Objekt mit allen Angaben zum übergeordneten Kalender. Z.B. gibt `calendar.title` den Kalender-Namen aus. |
| `eventStatus`     | string | `draft`, `booking_open`, `fully_booked`, `waiting_list_open`, `not_bookable`, `not_yet_bookable`, `booking_closed`                         |
| `canRegister`     | bool   | Zeigt, ob eine Buchung (auf Warteliste) möglich ist.                                                                                       |
| `isFullyBooked`   | bool   | Zeigt, ob der Event ausgebucht ist.                                                                                                        |
| `bookingCount`    | int    | Zeigt, die Anzahl Buchungen an.                                                                                                            |
| `freeSpotsCount`  | int    | Zeigt die Anzahl freier Plätze an.                                                                                                         |
| `waitingListOpen` | bool   | Zeigt an, ob die Warteliste geöffnet ist.                                                                                                  |
| `hasLoggedInUser` | bool   | Zeigt an, ob ein Mitglied angemeldet ist.                                                                                                  |
| `loggedInUser`    | null   | FrontendUser Gibt null oder das FrontendUser Objekt zurück.                                                                                |

## Event Teilnehmer als CSV-Datei herunterladen (Encoding richtig einstellen)

Die Teilnehmer eines Events lassen sich im Backend als CSV-Datei (Excel) herunterladen.
In der `config/config.yaml` lässt sich das Encoding einstellen.
Standardmässig werden die Daten im Format **UTF-8** exportiert.
Es kann sein, dass Excel (bei entsprechender Einstellung), dann Umlaute falsch darstellt.
Das Problem kann behoben werden, wenn die `config/config.yaml` dahingehend anpasst wird,
dass die Inhalte vor dem Export von **UTF-8** nach **ISO-8859-1** konvertiert werden.

```
markocupic_calendar_event_booking:
  member_list_export:
    enable_output_conversion: true
    convert_from: 'UTF-8'
    convert_to: 'ISO-8859-1'
```

## Konfiguration `config/config.yaml`

```
# config/config.yaml
markocupic_calendar_event_booking:
    auto_expire_reserved_bookings: true  # Unbestätigte Anmeldungen/Anmeldungen mit nicht erledigten Zahlungen werden nach Ablauf einer konfigurierbaren Zeit (auto_expire_time_limit) automatisch abgelehnt.
    auto_expire_time_limit: 86400 # Zeit in Sekunden, welche der User hat, um seine Buchung per Link zu bestätigen oder um die Zahlung zu erledigen.
    auto_delete_expired_bookings: true # Abgelehnte Anmeldungen werden automatisch aus der Datenbank gelöscht.
    auto_delete_canceled_bookings: true # Stornierte Anmeldungen werden automatisch aus der Datenbank gelöscht.
    rate_limiter:
        event_booking_form: # Gebrauch des Buchungsformulars begrenzen
            policy: 'fixed_window'
            limit: 10 # default 5
            interval: '20 minutes' # default '15 minutes'
    member_list_export:
        enable_output_conversion: true
        convert_from: 'UTF-8'
        convert_to: 'ISO-8859-1'
```

## Checkout Template updatesicher anpassen

Das Standard Checkout template befindet sich unter `vendor/markocupic/calendar-event-booking-bundle/templates/Checkout/default.html.twig`.

Um das Original-Template zu überschreiben, muss ein neues/angepasstes Template im Projekt-ROOT unter
`templates/bundles/MarkocupicCalendarEventBookingBundle/Checkout/default.html.twig` angelegt werden.
