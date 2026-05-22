:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
@echo off
cd /d "%~dp0"

REM jetzt bist du IMMER im Ordner der .bat
cd..
cd..
cd..
cd..
cd..

..\..\..\vendor\bin\ecs check tests --fix --config tools/ecs/config/default.php

cd tools/ecs/batch/fix

