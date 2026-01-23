# API changes

## Version 6.* to 7.0

### Umbenennung der Frontend-Module

- Das Frontend Modul `calendar_event_booking_event_booking_module` wurde umbenannt zu `event_booking_form`.
- Das Frontend Modul `calendar_event_booking_member_list_module` wurde umbenannt zu `event_booking_member_list`.
- Das Frontend Modul `calendar_event_booking_unsubscribe_from_event_module` wurde umbenannt zu `event_booking_unsubscribe`.

### Neues Frontend-Modul `event_booking_opt_in`

Neu gibt es ein zusätzliches Frontend Modul `event_booking_opt_in`.
Das Modul sollte auf der Seite platziert werden, wo User hingeleitet werden, wenn Sie den Bestätigungslink in der Benachrichtigung anklicken.

### Neues Frontend-Modul `event_booking_checkout`

Neu gibt es ein zusätzliches Frontend Modul `event_booking_checkout`.
Das Modul sollte auf der Seite platziert werden, wo User hingeleitet werden, nachdem sie das Buchungsformular abgeschickt haben. Das Modul kann eine optionale Zahlung (kostenpflichtig) abwickeln.

### Umbenennung von Spalten in `tl_calendar_events`
- Umbenennung von `tl_calendar_events.addBookingForm` nach `tl_calendar_events.enableBookingForm`
- Umbenennung von `tl_calendar_events.eventBookingNotificationCenterIds` nach `tl_calendar.subscribeNotification`
  Neu ist es nur noch möglich **eine** Benachrichtigung auszuwählen.
- Umbenennung von `tl_calendar_events.maxEscortsPerMember` nach `tl_calendar_events.maxEscortsPerBooking`
- Umbenennung von `tl_calendar_events.minMembers` nach `tl_calendar_events.minBookings`
- Umbenennung von `tl_calendar_events.maxMembers` nach `tl_calendar_events.maxBookings`

### Neue Spalten in `tl_calendar_events`

- Einführung von `tl_calendar_events.maxTicketsPerBooking`
- Einführung von `tl_calendar_events.enableWaitingList`
- Einführung von `tl_calendar_events.maxWaitingList`
-
### Neue Spalten in `tl_calendar`
- Einführung von `tl_calendar.eventUnsubscribePage`
- Einführung von `tl_calendar.eventBookingOptInPage`
- Einführung von `tl_calendar.eventBookingCheckoutHandler`
- Einführung von `tl_calendar.paymentSuccessNotification`
- Einführung von `tl_calendar.subscribeNotification`
- Einführung von `tl_calendar.waitingListAdvancementNotification`
- Einführung von `tl_calendar.unsubscribeNotification`
- Einführung von `tl_calendar.optInSuccessNotification`
- Einführung von `tl_calendar.requireOptIn`
- Einführung von `tl_calendar.emailUnique`

### Neue Spalte `tl_calendar_events_member.ticketAmount` und neue Funktion von `tl_calendar_events_member.escorts`

Das Feld `tl_calendar_events_member.ticketAmount` wird neu verwendet, wenn bei einer Buchung mehrere Tickets erworben werden sollen.
**Achtung!** Die Anzahl Tickets wird immer zur Gesamtzahl der gebuchten Plätze addiert.
Soll die Anzahl Tickets im Formular einstellbar sein, muss dazu im Buchungsformular ein Feld mit dem Namen `ticketAmount` erstellt werden.

Das Feld `tl_calendar_events_member.escorts` sollte benutzt werden, wenn bei einer Buchung die Zahl der Begleitpersonen angegeben werden muss.
**Achtung!** Die Zahl der Begleitpersonen wird neu **nie** zur Gesamtzahl der gebuchten Plätze addiert.

Bei der Migration wurde der Inhalt von `tl_calendar_events_member.escorts` in `tl_calendar_events_member.ticketAmount` kopiert, wenn die Zahl der Begleitpersonen zur Gesamtzahl der Event-Teilnehmer addiert wird.

### Weitere neue Spalten `tl_calendar_events_member`
- Einführung von `tl_calendar_events_member.addedOn`
- Einführung von `tl_calendar_events_member.canceled`
- Einführung von `tl_calendar_events_member.checkoutHandler`
- Einführung von `tl_calendar_events_member.expired`
- Einführung von `tl_calendar_events_member.formSubmit`
- Einführung von `tl_calendar_events_member.form`
- Einführung von `tl_calendar_events_member.optIn`.
- Einführung von `tl_calendar_events_member.temporaryReserved`
- Einführung von `tl_calendar_events_member.ticketAmount`
- Einführung von `tl_calendar_events_member.waitingList`

### Einführung der neuen Tabelle in `tl_calendar_events_payment`

### Neue Einträge in der Bundle Configuration -> `config.yaml`

```yaml
# config/config.yaml
markocupic_calendar_event_booking:
    auto_expire_reserved_bookings: true  # Unbestätigte Anmeldungen werden nach Ablauf einer konfigurierbaren Zeit (auto_expire_time_limit) automatisch abgelehnt.
    auto_expire_time_limit: 86400 # Zeit in Sekunden, welche der User hat, um seine Buchung per Link zu bestätigen oder um die Zahlung zu erledigen.
    auto_delete_expired_bookings: true # Abgelehnte Anmeldungen werden automatisch aus der Datenbank gelöscht.
    auto_delete_canceled_bookings: true # Stornierte Anmeldungen werden automatisch aus der Datenbank gelöscht.
```

### Wegfall mehrerer Simple Tokens für Benahrichtigungen
- Wegfall von `##event_startDateFormatted##`. Kann mit `{{format_date::##event_startDate##::d.m.Y}}` ersetzt werden.
- Wegfall von `##event_endDateFormatted##`. Kann mit `{{format_date::##event_endDate##::d.m.Y}}` ersetzt werden.
- Wegfall von `##event_startTimeFormatted##`. Kann mit `{{format_date::##event_startTime##::d.m.Y}}` ersetzt werden.
- Wegfall von `##event_endTimeFormatted##`. Kann mit `{{format_date::##event_endTime##::d.m.Y}}` ersetzt werden.

### Umstellung aller Templates für die Frontend-Module auf Twig
Alle Templates sind neu in Twig geschrieben.

### Wegfall des Partial Templates für die Auflistung der Buchungen
Das Template `partial_event_booking_member_list_partial.html5` wurde entfernt und ist neu in `mod_event_booking_member_list.html.twig` enthalten.

### Neue Benachrichtigungstypen
- Neuer Benachrichtigungstyp `event-booking-opt-in-success-notification`
- Neuer Benachrichtigungstyp `event-confirm-notification`
- Neuer Benachrichtigungstyp `waiting-list-advancement-notification`
- Neuer Benachrichtigungstyp `event-booking-payment-success-notification`

### Einführung neuer Cronjobs
- `CheckWaitingListCron`: Lässt Buchungen auf der Warteliste automatisch nachrücken, wenn Plätze frei werden.
- `HandleCanceledBookingCron`: Löscht stornierte Einträge aus der Datenbank, wenn so konfiguriert.
- `HandleTemporaryReservedBookingsCron`: Setzt temporär reservierte Buchungen nach Ablauf einer konfigurierbaren Frist auf `expired` und löscht diese gegebenenalls aus der Datenbank (ebenfalls konfigurierbar).
