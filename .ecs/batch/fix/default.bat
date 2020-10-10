:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
:: src
vendor\bin\ecs check vendor/markocupic/calendar-event-booking-bundle/src --fix --config vendor/markocupic/calendar-event-booking-bundle/.ecs/config/default.php
:: tests
vendor\bin\ecs check vendor/markocupic/calendar-event-booking-bundle/tests --fix --config vendor/markocupic/calendar-event-booking-bundle/.ecs/config/default.php
::
cd vendor/markocupic/calendar-event-booking-bundle/.ecs./batch/fix
