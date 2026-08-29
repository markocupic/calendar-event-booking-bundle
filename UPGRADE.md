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

### Neue Spalte `tl_calendar_events_payment.settlementCurrencyCode`

Eine Zahlung hat zwei Währungen, die nicht dieselbe sein müssen:

- `currencyCode` ist die Währung des Bruttobetrags – die also, die der Kunde bezahlt hat.
- `settlementCurrencyCode` ist die Währung, in der der Zahlungsanbieter abgerechnet hat – also die
  Währung des Kontos, dem das Geld gutgeschrieben wird. Sie gilt für `netAmountReceived` und `captureFee`.

Bei den allermeisten Installationen sind beide gleich. Sie sind es nicht, wenn das Anbieterkonto in
einer anderen Währung geführt wird als die Preise im Event – Stripe meldet Gebühr und Nettobetrag
dann in der Kontowährung, während der Bruttobetrag in der Belastungswährung bleibt.

Ein leerer Wert bedeutet, dass der Anbieter die Abrechnungswährung nicht gemeldet hat. Er bedeutet
**nicht**, dass sie mit `currencyCode` übereinstimmt.

### Neue Spalten `tl_calendar_events_payment.exchangeRate` und `settlementGrossAmount`

Sobald die beiden Währungen auseinanderfallen, geht eine Zahlungszeile ohne diese beiden Werte nicht
auf: `netAmountReceived` plus `captureFee` ergibt dann nicht `grossAmount`, weil die Beträge in
verschiedenen Währungen stehen.

- `exchangeRate` ist der Kurs, den der Anbieter angewendet hat, in voller Genauigkeit. `1`, wenn beide
  Währungen gleich sind.
- `settlementGrossAmount` ist `grossAmount`, umgerechnet in die Abrechnungswährung.

Damit gilt wieder eine einfache Regel:
`settlementGrossAmount − captureFee = netAmountReceived`, alle drei in `settlementCurrencyCode`.

Beispiel einer echten Zahlung – EUR-Preis, Schweizer Stripe-Konto:

| Spalte | Wert |
| --- | --- |
| `grossAmount` / `currencyCode` | 217.18 EUR |
| `exchangeRate` | 0.93564 |
| `settlementGrossAmount` | 203.20 CHF |
| `captureFee` | 10.96 CHF |
| `netAmountReceived` | 192.24 CHF |
| `settlementCurrencyCode` | CHF |

Bestehende Zahlungen werden von der Migration `SettlementCurrencyCodeMigration` befüllt: für sie gilt
Abrechnungswährung = Belastungswährung, Bruttobetrag = Bruttobetrag und Kurs = 1.

### Neue Spalten `tl_calendar_events_payment.refundExchangeRate` und `refundSettlementAmount`

Dasselbe Muster für die Rückerstattungen. Eine eigene Währungsspalte brauchen sie nicht – eine
Rückerstattung erfolgt immer in der Währung der ursprünglichen Belastung (`currencyCode`), und ihre
Gebühr fällt auf demselben Konto an wie die der Zahlung (`settlementCurrencyCode`).

Was ihnen fehlte, war der **Kurs**: Der Anbieter erzeugt für die Rückerstattung eine eigene
Umrechnung, zum Kurs des Tages, an dem sie ausgelöst wurde. Wird eine Zahlung Monate später
rückerstattet, wird dem Konto ein anderer Betrag belastet, als ihm seinerzeit gutgeschrieben wurde –
ein echter Kursverlust, der ohne diese Spalten nirgends erklärbar ist.

- `refundExchangeRate` – der Kurs der Rückerstattung, **nicht** der der Zahlung.
- `refundSettlementAmount` – `refundAmount`, umgerechnet in die Abrechnungswährung, vor Abzug von
  `refundFee`.

Ausserdem steht `providerRefundId` jetzt in der Palette. Das Feld war deklariert, aber im Backend
nicht erreichbar.

### Neue Spalte `log` – und `notes` gehört ab jetzt dem Backend-Benutzer

`tl_calendar_events_member`, `tl_calendar_events_order` und `tl_calendar_events_payment` haben eine
zweite Textspalte bekommen, und die Trennung dazwischen ist der Punkt:

- **`notes`** gehört dem Backend-Benutzer. Dort steht „am 22.8. von Hand zurückerstattet, siehe
  Mail". Weder die Grunderweiterung noch die Zahlungs-Plugins schreiben dort noch hinein.
- **`log`** gehört dem System und ist im Backend read-only. Dort landen die Zeilen, die früher in
  `notes` standen — dass eine Gebühr noch nicht gemeldet wurde, dass sie nachgetragen wurde, dass
  PayPal keine Capture zurückgegeben hat.

Damit teilt sich niemand mehr ein Feld mit einem Cronjob, und kein Cronjob muss auf einen Satz
Rücksicht nehmen, den gerade jemand tippt.

Geschrieben wird ins `log` ausschliesslich über die neue Klasse `Util\LogBuilder` – ein String rein,
ein String raus, ohne `save()`, damit der Aufrufer bestimmt, wann geschrieben wird:

```php
use Markocupic\CalendarEventBookingBundle\Util\LogBuilder;

$payment->log = LogBuilder::append($payment->log, 'Refunded manually, see ticket #4711.');
```

Ergebnis – eine Zeile pro Ereignis, Zeitstempel vorn, neue Zeilen unten:

```
2026-08-29 21:10 Stripe has not reported a balance transaction for this charge yet, …
2026-08-29 21:15 Stripe reported the balance transaction: 217.18 EUR converted at 0.93564 to …
```

Drei Regeln gehören dazu, sonst trägt die Konstruktion nicht:

- **Nur anhängen, nie löschen, nie überschreiben.** Die Abfolge der Zeilen ist die Geschichte der
  Zahlung; ein Feld, in dem immer nur der letzte Satz steht, ist ein Statusfeld — und davon gibt es
  in der Zeile bereits eines.
- **Englisch, immer, ohne Translator.** Das Feld ist gespeicherter Text, kein Label: es wird einmal
  geschrieben und danach unverändert angezeigt, lässt sich also nicht in der Sprache des Lesers neu
  rendern. Über die aktive Locale zu gehen entschiede die Sprache per Zufall — die erste Zeile
  entsteht im Frontend-Request (Seitensprache), die zweite im Cron, der weder Seite noch Sprachbaum
  kennt.
- **Nie zurücklesen.** Kein Vergleich, kein Regex, kein „steht unser Satz noch drin". Was Code
  entscheiden muss, entscheidet er an den typisierten Spalten daneben.

Und: nur bei einem Zustandswechsel schreiben. Ein minütlicher Job, der „immer noch nichts"
protokolliert, füllt den Datensatz einer hängenden Zahlung mit Tausenden Zeilen.

**Altbestand:** Zahlungen aus früheren Versionen haben ihren Satz in `notes` stehen. Er bleibt dort,
wo er ist — es gibt keine Migration, die Text zwischen den Spalten verschiebt oder ihm einen
Zeitstempel erfindet. Neue Zeilen kommen ins `log`.

**Was eine Zeile bekommt.** Nur echte Zustandswechsel, und nur solche, die der Datensatz überlebt:

| Datensatz | Ereignis | geschrieben in |
| --- | --- | --- |
| Buchung | Anlage über das Buchungsformular | `StoreFormDataListener` |
| Buchung | Opt-in über den Bestätigungslink | `EventBookingOptInController` |
| Buchung | Abmeldung über das Abmeldeformular | `EventBookingUnsubscribeController` |
| Buchung | Nachrücken von der Warteliste | `WaitingListPromotionProcessor` |
| Buchung | Ablauf der temporären Reservation | `HandleExpirableBookingsCron` |
| Order | Capture ohne Capture-Objekt (PayPal) | `CapturePaymentStep` |
| Zahlung | Gebühr noch nicht gemeldet / nachgetragen | Capture-Mapper, `CompleteSettlementDataCron` |

Eine im Backend von Hand angelegte Buchung durchläuft den Hook nicht und hat deshalb **gar keine**
Zeile. Das ist kein Versehen: ein leeres Log ist später das Einzige, woran sich die beiden
unterscheiden lassen.

Zwei naheliegende Kandidaten sind bewusst **nicht** dabei: `HandleCanceledBookingsCron` und
`DeleteExpiredBookingsCron` löschen den Datensatz. Eine Zeile in das Log einer Zeile zu schreiben,
die im selben Durchgang verschwindet, hilft niemandem — solche Vorgänge gehören ins `tl_log`, wo sie
auch stehen.

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
