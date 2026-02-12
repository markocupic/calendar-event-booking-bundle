<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use SlevomatCodingStandard\Sniffs\Variables\UnusedVariableSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
    ->withSets([SetList::CONTAO, \Markocupic\EasyCodingStandard\Set\SetList::MARKOCUPIC])
    ->withPaths([
        __DIR__.'/../../../src',
        __DIR__.'/../../../tests',
    ])
    ->withSkip([
        __DIR__.'/../../../contao',
        __DIR__.'/../../../public',
        __DIR__.'/../../../sql',
        __DIR__.'/../../../templates',
        __DIR__.'/../../../tools',
        __DIR__.'/../../../translations',
        MethodChainingIndentationFixer::class => [
            '*/DependencyInjection/Configuration.php',
        ],
        UnusedVariableSniff::class            => [
            // 'core-bundle/tests/Session/Attribute/ArrayAttributeBagTest.php',
        ],
    ])
    ->withRootFiles()
    ->withParallel()
    ->withSpacing(Option::INDENTATION_SPACES, "\n")
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => "This file is part of the Calendar Event Booking Bundle.\n\n(c) Marko Cupic <m.cupic@gmx.ch>\n@license MIT\nFor the full copyright and license information,\nplease view the LICENSE file that was distributed with this source code.\n@link https://github.com/markocupic/calendar-event-booking-bundle",
    ])
    ->withCache(sys_get_temp_dir().'/ecs_default_cache');
