# calendar-event-booking-bundle
!!! Achtung: Version 3.x ist nicht mehr zu 100% kompatibel zu Version 2.x. In Version >=3.x müssen die Simple Tokens im Notification-Center neu gesetzt werden. Für Eigenschaften die den Event betreffen, braucht es nun das prefix "event_" und für Eigenschaften, die den Teilnehmer betreffen das Präfix "member_".

Mit dieser Contao 4 Erweiterung werden Events buchbar. Die Extension erweitert die Tabelle tl_calendar_events um weitere Felder und erstellt eine zusätzliche Kindtabelle zu tl_calendar_events um die Anmeldungen der gebuchten Events zu speichern.

Mit einem Frontend Modul lässt sich auf einer Event-Reader Seite ein Anmeldeformular einblenden. 
Wichtig! Das Anmeldeformular zieht den Eventnamen aus der Url. Der Event-Alias oder die Event-Id muss deshalb zwingender Bestandteil der Url sein.

## Buchungsformular erstellen
Beim ersten Aufruf der Seite nach der Installation der Erweiterung wird ein Beispielformular automatisch generiert. Passen Sie dieses an.  **Wichtig!!! Dem Formular muss die ID "event-booking-form" vergeben werden, damit der validateForm Hook beim Absenden des Formulars aktiv wird.** Folgende Felder werden im Formular erstellt:
firstname,lastname,gender,dateOfBirth,street,postal,city,phone,email,escorts,notes
(Zusätliche gewünschte Felder können erstellt werden, müssen aber zuvor in tl_calendar_events_member angelegt werden.)

## E-Mail Buchungsbestätigung mit dem Notification Center
Aktivieren Sie beim Event die Buchungsbestätigung mit dem Notification Center und versenden Sie beim Absenden des Formulars eine oder mehrere Nachrichten an den Teilnehmer oder Eventorganisator.

![Notification Center](doc/notification_center.jpg?raw=true)

### Gebrauch der Simple Tokens im Notification Center
Teilnehmer:  ##member_gender## (male oder female), ##member_salutation## (Übersetzt: Herr oder Frau), ##member_email##, ##member_firstname##, ##member_street##, etc. (Feldnamen aus tl_calendar_events_member)

Event: ##event_title##, ##event_street##, ##event_postal##, ##event_city##, etc. (Feldnamen aus tl_calendar_events)

Organisator/Email-Absender: ##organizer_senderName##, ##organizer_senderEmail##, ##organizer_email, etc. (Feldnamen aus tl_user)

## CSV-Export der Teilnehmer
Nach dem Anmeldeprozess sind die angemeldeten Event Mitglieder im Backend einsehbar. Auch ist es möglich von den Teilnehmern einen CSV-Export aus dem Backend heraus zu machen.
