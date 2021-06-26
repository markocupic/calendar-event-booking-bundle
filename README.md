# Events buchen mit Contao
### Achtung: Bei der Migration von Version 3.x nach 4.x gab es mehrere Änderungen in der Benennung der Modul-Typen und Template-Namen. Dies bitte bei einer allfälligen Migration berücksichtigen.

## Events buchen
Mit dieser Contao 4 Erweiterung werden Events über ein Anmeldeformular buchbar.
Das Anmeldeformular kann im Contao Formulargenerator erstellt werden.
Die Erweiterung stellt während des Installationsprozesses ein Sample Anmeldeformular bereit, welches Grundansprüchen genügen sollte.
Die Werte des Formulars werden in der Datenbank in tl_calendar_events_member abgelegt
und sind im Backend einsehbar und über eine CSV-Datei exportierbar.

## Benachrichtigung
Event-Organisator und Teilnehmer können bei Event-Anmeldung und Event-Abmeldung über das Notification Center benachrichtigt werden.

## Frontend Module
#### Event Anmeldeformular
Mit einem Frontend Modul lässt sich auf einer Event-Reader Seite ein Event-Anmeldeformular einblenden.
Verlinken Sie in den Moduleinstellungen mit dem entsprechenden Formular aus dem Contao Formulargenerator.
Wichtig! Das Anmeldeformular zieht den Eventnamen aus der Url.
Der Event-Alias oder die Event-Id müssen deshalb zwingend als Parameter in der Url enthalten sein.
Das Anmeldeformular sollte deshalb idealerweise immer in Kombination mit dem Event-Reader-Modul eingebunden werden.

#### Angemeldete Mitglieder im Frontend auflisten
Mit einem weiteren Frontend Modul können zu einem Event bereits angemeldete Personen aufgelistet werden.
Wichtig! Das Auflistungsmodul zieht den Eventnamen aus der Url.
Der Event-Alias oder die Event-Id müssen deshalb zwingend als Parameter in der Url enthalten sein.
Das Mitgliederauflistungs-Modul sollte deshalb idealerweise immer in Kombination mit dem Event-Reader-Modul eingebunden werden.

#### Von Event abmelden
Die Erweiterung stellt auch eine Möglichkeit sich von einem Event wieder abzumelden.
Via Notification Center kann dem Teilnehmer ein Abmeldelink (##event_unsubscribeHref##) zugeschickt werden.
Erstellen Sie das entsprechende Modul und binden Sie es auf einer neuen Seite in der Seitenstruktur ein.
Diese Seite sollten Sie sinnvollerweise in der Navigation nicht anzeigen lassen.
In der Kalendereinstellung legen Sie anschliessend fest, auf welcher Seite das Event-Abmeldeformular liegt.

## Einrichtung (Ablauf)
1. Kalender und Events anlegen.
2. "Eventliste" und "Eventleser" Frontend-Module anlegen.
3. Falls nicht schon geschehen, E-Mail-Gateway (Notification Center) anlegen.
4. Benachrichtigung des Typs "Event-Buchungsbestätigung" anlegen (Notification Center)
5. Im Contao Formulargenerator die benötigten Felder bereitstellen.
6. Das Frontend Modul "Event-Buchungsformular" erstellen und in den Modul-Einstellungen das bei Punkt 5 erstellte Formular auswählen. Danach noch die bei Punkt 4 erstellte Benachrichtigung auswählen.
7. Die 3 erstellten Module (Eventliste, Eventleser und Event-Buchungsformular) in der Contao Seitenstruktur einbinden (Wichtig! Event-Buchungsformular und Eventleser gehören auf die gleiche Seite).
8. Optional das Frontend Modul "Event-Abmeldeformular" mit dazugehörender Benachrichtigung "Event-Abmeldung" erstellen und dieses in einer extra dafür erstellten Seite einbinden.
9. Optional das Frontend Modul "Event-Mitglieder-Auflistung" erstellen und auf der Seite mit dem Eventleser Modul einbinden.
10. Bei den Events die Buchungs-, Benachrichtigungs- und Abmeldeoptionen konfigurieren.
11. In der Kalendereinstellung die Seite mit dem "Event-Abmeldeformular" festlegen.

#### Punkt 4: E-Mail Benachrichtigung im Notification Center konfigurieren
Versenden Sie beim Absenden des Formulars eine oder mehrere Nachrichten an den Teilnehmer oder eine Kopie an den Eventorganisator
und nutzen Sie dabei die **Simple Tokens**.

Mit ##event_unsubscribeHref## kann ein tokengesicherter Event-Abmeldelink mitgesandt werden. Dazu muss aber im Event die Event-Abmeldung erlaubt werden.
Auch sollte das dafür nötige Frontend Modul "Event-Abmeldeformular" erstellt und in einer Seite eingebunden worden sein.
![Notification Center](src/Resources/docs/notification_center.jpg?raw=true)

##### Gebrauch der Simple Tokens im Notification Center
Teilnehmer:  ##member_gender## (male oder female), ##member_salutation## (Übersetzt: Herr oder Frau), ##member_email##, ##member_firstname##, ##member_street##, etc. (Feldnamen aus tl_calendar_events_member)

Event: ##event_title##, ##event_street##, ##event_postal##, ##event_city##, ##event_unsubscribeLimitTstamp##, ##event_unsubscribeLimitDate##, ##event_unsubscribeLimitDatim##, etc. (Feldnamen aus tl_calendar_events)

Organisator/Email-Absender: ##organizer_senderName##, ##organizer_senderEmail##, ##organizer_email, etc. (Feldnamen aus tl_user)


#### Punkt 5: Event-Buchungsformular erstellen
Beim ersten Aufruf der Seite nach der Installation der Erweiterung wird **automatisch** ein Beispielformular mit allen benötigten Feldern generiert.
**Wichtig!!! Im Formular muss die Checkbox "Aktiviere Event-Buchungsformular-Funktion" aktiviert sein.** Weitere Einstellungen müssen keine zwingend gemacht werden.
![Formulargenerator-Einstellung](src/Resources/docs/form_generator.jpg?raw=true)
Folgende Felder können im Formular erstellt werden:
firstname,lastname,gender,dateOfBirth,street,postal,city,phone,email,escorts,notes


Werden weitere Felder gewünscht, so müssen diese in app/Resources/contao/dca/tl_calendar_events_member.php definiert werden und danach via das Installtool in der Datenbank angelegt werden.
```php
<?php
//app/Resources/contao/dca/tl_calendar_events_member.php

$GLOBALS['TL_DCA']['tl_calendar_events_member']['fields']['foodHabilities'] = [
    'exclude'   => true,
    'search'    => true,
    'sorting'   => true,
    'inputType' => 'select',
    'options'   => ['vegetarian', 'vegan'],
    'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];
// Add custom fields to palette and make fields visible in the Contao backend.
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('food_legend', 'personal_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(['foodHabilities'], 'food_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar_events_member');

```


#### Punkt 10: E-Mail Buchungsbestätigung im Event aktivieren
Aktivieren Sie beim Event die Buchungsbestätigung mit dem Notification Center, wählen Sie eine Benachrichtigung aus und legen Sie einen Absender mit einer gültigen E-Mail-Adresse (tl_user) fest.
![Benachrichtigung im Event aktivieren](src/Resources/docs/benachrichtigung-aktivieren.jpg?raw=true)

### Template Variablen

Folgende zusätzliche Template Variablen sind in allen Kalender-Templates einsetzbar:
Tag | type | Erklärung 
------------ |------------- |--
`$this->canRegister` | bool | Zeigt, ob eine Registrierung möglich ist.
`$this->isFullyBooked` | bool | Zeigt, ob der Event ausgebucht ist.
`$this->bookingCount` | int | Zeigt, die Anzahl Registrierungen an.
`$this->bookingMin` | int | Zeigt, die minimal verlangte Teilnehmerzahl an.
`$this->bookingMax` | int | Zeigt, die maximale Teilnehmerzahl an.
`$this->bookingStartDate` | int | Zeigt, die Buchungsstartzeit (timestamp) an.
`$this->bookingEndDate` | int | Zeigt, die Buchungsendzeit (timestamp) an.
