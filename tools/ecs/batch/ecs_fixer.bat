:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/markocupic/calendar-event-booking-bundle/src --fix --config vendor/markocupic/calendar-event-booking-bundle/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/calendar-event-booking-bundle/contao --fix --config vendor/markocupic/calendar-event-booking-bundle/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/calendar-event-booking-bundle/config --fix --config vendor/markocupic/calendar-event-booking-bundle/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/calendar-event-booking-bundle/tests --fix --config vendor/markocupic/calendar-event-booking-bundle/tools/ecs/config.php
