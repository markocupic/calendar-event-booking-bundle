# calendar-event-booking-bundle
!!! Achtung: Version 3.x ist nicht mehr zu 100% kompatibel zu Version 2.x. In Version >=3.x müssen die Simple Tokens im Notification-Center neu gesetzt werden. Für Eigenschaften die den Event betreffen, braucht es nun das prefix "event_" und für Eigenschaften, die den Teilnehmer betreffen das Präfix "member_" und die Eigenschaften des des Event-Organisators werden über "organizer_" erreicht.

Mit dieser Contao 4 Erweiterung werden Events über ein Anmeldeformular buchbar. Event-Organisator und Teilnehmer können über das Notification Center benachrichtigt werden. Bei der Installation wird die Tabelle tl_calendar_events um weitere Felder erweitert. Zusätzlich wird eine Kindtabelle (tl_calendar_events_member) erstellt, um die Event-Buchungen abzulegen.

Mit einem Frontend Modul lässt sich auf einer Event-Reader Seite ein Event-Anmeldeformular einblenden. 
Wichtig! Das Anmeldeformular zieht den Eventnamen aus der Url. Der Event-Alias oder die Event-Id müssen deshalb zwingend als Parameter in der Url enthalten sein.


## Einrichtung (Ablauf)
1. Events anlegen
2. Event-Listing und Event-Reader Frontend-Module anlegen.
3. Falls nicht schon geschehen, E-Mail-Gateway (Notification Center) anlegen
4. Benachrichtigung des Typs "Event Buchungsbestätigung" anlegen (Notification Center)
5. "Event-Buchungsformular" Frontend-Modul anlegen und mit der bei Punkt 4 erstellten Benachrichtigung verknüpfen.
6. Die 3 erstellten Module in der Contao Seitenstruktur einbinden (Wichtig! Buchungsformular und Eventreader gehören auf die gleiche Seite). 
7. Bei den Events die Buchungs- und Benachrichtigungsoptionen konfigurieren


## Punkt 4: E-Mail Benachrichtigung im Notification Center konfigurieren
Versenden Sie beim Absenden des Formulars eine oder mehrere Nachrichten an den Teilnehmer oder eine Kopie an den Eventorganisator und nutzen Sie dabei die **Simple Tokens**.
![Notification Center](doc/notification_center.jpg?raw=true)

#### Gebrauch der Simple Tokens im Notification Center
Teilnehmer:  ##member_gender## (male oder female), ##member_salutation## (Übersetzt: Herr oder Frau), ##member_email##, ##member_firstname##, ##member_street##, etc. (Feldnamen aus tl_calendar_events_member)

Event: ##event_title##, ##event_street##, ##event_postal##, ##event_city##, etc. (Feldnamen aus tl_calendar_events)

Organisator/Email-Absender: ##organizer_senderName##, ##organizer_senderEmail##, ##organizer_email, etc. (Feldnamen aus tl_user)


## Punkt 5: Event-Buchungsformular erstellen
Beim ersten Aufruf der Seite nach der Installation der Erweiterung wird automatisch ein Beispielformular generiert. Passen Sie dieses an.  
**Wichtig!!! Dem Formular muss die ID "event-booking-form" vergeben werden, damit der validateForm Hook beim Absenden des Formulars aktiv wird.** 
Folgende Felder werden im Formular erstellt:
firstname,lastname,gender,dateOfBirth,street,postal,city,phone,email,escorts,notes
(Zusätliche gewünschte Felder können erstellt werden, müssen aber zuvor in tl_calendar_events_member angelegt werden.)


## Punkt 7: E-Mail Buchungsbestätigung im Event aktivieren
Aktivieren Sie beim Event die Buchungsbestätigung mit dem Notification Center, wählen Sie eine Benachrichtigung aus und legen Sie einen Absender mit einer gültigen E-Mail-Adresse (tl_user) fest.
![Benachrichtigung im Event aktivieren](doc/benachrichtigung-aktivieren.jpg?raw=true)


## CSV-Export der Teilnehmer
Nach dem Anmeldeprozess sind die angemeldeten Event Mitglieder im Backend einsehbar. Auch ist es möglich von den Teilnehmern einen CSV-Export (Teilnehmerliste) aus dem Backend heraus zu machen.
