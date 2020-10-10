<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;

return static function (ContainerConfigurator $containerConfigurator): void {

    $services = $containerConfigurator->services();

    $services
        ->set(HeaderCommentFixer::class)
        ->call('configure', [[
            'header' => "This file is part of markocupic/calendar-event-booking-bundle.\n\n(c) Marko Cupic ".date('Y')." <m.cupic@gmx.ch>\n@license MIT\nFor the full copyright and license information, please view the LICENSE\nfile that was distributed with this source code.\n@link https://github.com/markocupic/calendar-event-booking-bundle",
        ]])
    ;
};