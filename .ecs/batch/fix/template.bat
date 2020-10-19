:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
:: templates
start vendor\bin\ecs check vendor/markocupic/calendar-event-booking-bundle/src/Resources/contao/templates --fix --config vendor/markocupic/calendar-event-booking-bundle/.ecs/config/template.php
::
cd vendor/markocupic/calendar-event-booking-bundle/.ecs./batch/fix
