# calendar-event-booking-bundle
Mit dieser Contao 4 Erweiterung werden Events buchbar. Die Extension erweitert die Tabelle tl_calendar_events um weitere Felder und erstellt eine zusätzliche Kindtabelle zu tl_calendar_events um die Anmeldungen der gebuchten Events zu speichern.

Mit einem Frontend Modul lässt sich auf einer Event-Reader Seite ein Anmeldeformular einblenden.
Wichtig! Das Anmeldeformular zieht den Eventnamen aus der Url. Der Event-Alias oder die Event-Id muss deshalb zwingender Bestandteil der Url sein.

Zu jedem Event lassen sich zusätzlich im Backend die Event Teilnehmer einsehen und als csv-Datei downloaden.

## Contao Bundle lokal und nicht von packagist oder einem anderen Repository laden
Möchte man das Bundle aus einer lokalen Quelle laden und dann unter `contao/managed-edition` laufen lassen, sind folgende Schritte notwendig:
Erstellen der globale ContaoManagerPlugin Klasse, welche in einem Symfony App nur einmal vorkommt und im Verzeichnis `app/` angesiedelt sein sollte.

```php
// Symfony Root => app/ContaoManagerPlugin.php
<?php
/**
 * @copyright  Marko Cupic 2017 <m.cupic@gmx.ch>
 * @author     Marko Cupic
 * @package    Calendar Event Booking Bundle
 * @license    LGPL-3.0+
 * @see        https://github.com/markocupic/calendar-event-booking-bundle
 * @see        https://github.com/markocupic/employee-bundle
 *
 */

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

/**
 * Plugin for the Contao Manager.
 *
 * @author Marko Cupic
 */
class ContaoManagerPlugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            // 1. lokales Plugin in ROOT  src/markocupic/calendar-event-booking-bundle
            BundleConfig::create('Markocupic\CalendarEventBookingBundle\MarkocupicCalendarEventBookingBundle')
                ->setLoadAfter([
                  'Contao\CoreBundle\ContaoCoreBundle',
                  'Contao\CalendarBundle\ContaoCalendarBundle'
                ]),
            // 2. lokales Plugin in ROOT  src/markocupic/employee-bundle
            BundleConfig::create('Markocupic\EmployeeBundle\MarkocupicEmployeeBundle')
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle'])
            // Weitere lokale Plugins
        ];
    }
}
```
Dieser Schritt ersetzt den Eintrag in der `app/AppKernel.php` bei einer `contao/standard-edition` Installation.

#### !!! Wichtig - Wichtig!!!
Falls im lokalen Bundle unter ```src/vendorname/bundlename/src/ContaoManager``` eine Klasse  ```Plugin``` abgelegt ist, sollte diese gelöscht oder (etwas weniger brachial) das übergeordnete Verzeichnis ```src/vendorname/bundlename/src/ContaoManager``` umbenannt werden, damit der ContaoManager nicht dazwischenschiesst und das Laden des Bundles verhindert.

### Anpassen der composer.json im ROOT

Damit die Plugin Klasse und die Erweiterung auch vom Composer Class Autoloader gefunden wird muss folgendes in der `composer.json` eingetragen sein:
```json
"autoload": {
    "classmap": [
        "src/,
        "app/ContaoManagerPlugin.php"
    ],
    "psr-4": {
        "Markocupic\\CalendarEventBookingBundle\\": "src/"
    }
}
```

### composer install --optimize-autoloader
Jetzt noch ein beherztes

```sh
$ composer install --optimize-autoloader
```
und ein
```sh
$ bin/console cache:clear --env=prod
```
und die Erweiterung sollte nun laufen.

Anmerkung: vendor/bin/contao-console.
Eine Symfony Applikation enthält normalerweise ein Consolen-Script `bin/console`. In der Contao Managed Edition ist das Konsolenskript Teil des contao/manager-bundle und in `vendor/bin/contao-console` installiert. Deshalb mit `cd vendor/bin` ins entsprechende Verzeichnis wechseln und dann das Konsolenscript ausführen.

```sh
$ cd bin/console
$ php contao-console cache:clear --env=prod
```
