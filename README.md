# calendar-event-booking-bundle
!!! Achtung: Version 3.x ist nicht mehr zu 100% kompatibel zu Version 2.x. In Version >=3.x müssen die Simple Tokens im Notification-Center neu gesetzt werden. Für Eigenschaften die den Event betreffen, braucht es nun das prefix "event_" und für Eigenschaften, die den Teilnehmer betreffen das Präfix "member_" und die Eigenschaften des des Event-Organisators werden über "organizer_" erreicht.

## Events buchen
Mit dieser Contao 4 Erweiterung werden Events über ein Anmeldeformular buchbar. Event-Organisator und Teilnehmer können über das Notification Center benachrichtigt werden. Bei der Installation wird die Tabelle tl_calendar_events um weitere Felder erweitert. Zusätzlich wird eine Kindtabelle (tl_calendar_events_member) erstellt, um die Event-Buchungen abzulegen.

Mit einem Frontend Modul lässt sich auf einer Event-Reader Seite ein Event-Anmeldeformular einblenden. 
Wichtig! Das Anmeldeformular zieht den Eventnamen aus der Url. Der Event-Alias oder die Event-Id müssen deshalb zwingend als Parameter in der Url enthalten sein.

## Angemeldete Mitglieder im Frontend sichtbar machen
Mit einem weiteren Frontend Modul können zu einem Event bereits angemeldete Personen sichtbar/aufgelistet werden. Dieses Modul muss zusammen mit einem Event-Reader Modul auf der selben Seite placiert werden.

## Von Event abmelden
Seit Version 3.3 kann auch eine Event Abmeldemöglichkeit im Event eingestellt werden. Dazu muss das passende Modul in der Seitenstruktur angelegt werden. Die Abmeldung erfolgt über einen Token gesicherten Link (##event_unsubscribeHref## Token), welcher mit dem Bestätigungs-E-Mail dem Benutzer mitgesandt werden kann.

## Einrichtung (Ablauf)
1. Events anlegen
2. Event-Listing und Event-Reader Frontend-Module anlegen.
3. Falls nicht schon geschehen, E-Mail-Gateway (Notification Center) anlegen
4. Benachrichtigung des Typs "Event Buchungsbestätigung" anlegen (Notification Center)
5. "Event-Buchungsformular" Frontend-Modul anlegen und mit der bei Punkt 4 erstellten Benachrichtigung verknüpfen.
6. Die 3 erstellten Module in der Contao Seitenstruktur einbinden (Wichtig! Buchungsformular und Eventreader gehören auf die gleiche Seite). 
7. Evtl. das Frontend Module "Event Abmeldeformular" erstellen und dieses in extra dafür erstellten Seite einbinden. 
9. Bei den Events die Buchungs-, Benachrichtigungs- und Abmeldeoptionen konfigurieren. 
10. In der Kalendereinstellung die Abmeldeseite festlegen. "tl_calendar.eventUnsubscribePage"


## Punkt 4: E-Mail Benachrichtigung im Notification Center konfigurieren
Versenden Sie beim Absenden des Formulars eine oder mehrere Nachrichten an den Teilnehmer oder eine Kopie an den Eventorganisator und nutzen Sie dabei die **Simple Tokens**.

Mit dem ##event_unsubscribeHref## Token kann der Event-Abmeldelink mitgesandt werden. Dazu muss aber im Event die Event-Abmeldung erlaubt werden. Auch sollte das nötige Frontend Modul "Event Abmeldeformular" erstellt und in einer Seite eingebunden wordsen sein. 
![Notification Center](doc/notification_center.jpg?raw=true)

#### Gebrauch der Simple Tokens im Notification Center
Teilnehmer:  ##member_gender## (male oder female), ##member_salutation## (Übersetzt: Herr oder Frau), ##member_email##, ##member_firstname##, ##member_street##, etc. (Feldnamen aus tl_calendar_events_member)

Event: ##event_title##, ##event_street##, ##event_postal##, ##event_city##, etc. (Feldnamen aus tl_calendar_events)

Organisator/Email-Absender: ##organizer_senderName##, ##organizer_senderEmail##, ##organizer_email, etc. (Feldnamen aus tl_user)


## Punkt 5: Event-Buchungsformular erstellen
Beim ersten Aufruf der Seite nach der Installation der Erweiterung wird **automatisch** ein Beispielformular mit allen benötigten Feldern generiert. 
**Wichtig!!! Im Formular muss die Checkbox "Aktiviere Event-Buchungsformular-Funktion" aktiviert sein.** Weitere Einstellungen müssen keine zwingend gemacht werden.
![Formulargenerator-Einstellung](doc/form_generator.jpg?raw=true) 
Folgende Felder können im Formular erstellt werden:
firstname,lastname,gender,dateOfBirth,street,postal,city,phone,email,escorts,notes
(Zusätliche gewünschte Felder können erstellt werden, müssen aber zuvor in tl_calendar_events_member angelegt werden.)


## Punkt 9: E-Mail Buchungsbestätigung im Event aktivieren
Aktivieren Sie beim Event die Buchungsbestätigung mit dem Notification Center, wählen Sie eine Benachrichtigung aus und legen Sie einen Absender mit einer gültigen E-Mail-Adresse (tl_user) fest.
![Benachrichtigung im Event aktivieren](doc/benachrichtigung-aktivieren.jpg?raw=true)


## CSV-Export der Teilnehmer
Nach dem Anmeldeprozess sind die angemeldeten Event Mitglieder im Backend einsehbar. Auch ist es möglich von den Teilnehmern einen CSV-Export (Teilnehmerliste) aus dem Backend heraus zu machen.
