<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AddField;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Codefog haste "Add field Hook".
 *
 * @Hook(Escort::HOOK, priority=Escort::PRIORITY)
 */
final class Escort
{
    public const HOOK = 'calEvtBookingAddField';
    public const PRIORITY = 1000;

    private static bool $disableHook = false;

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(Form $objForm, string $strField, array $arrDca, CalendarEventsModel $objEvent, CalendarEventBookingEventBookingModuleController $moduleInstance): bool
    {
        $arrDisabledHooks = $moduleInstance->getProperty('disabledHooks');

        if (\in_array(self::class, $arrDisabledHooks, true)) {
            return true;
        }

        // Skip input field "escorts" if escorts are not allowed
        if ('escorts' === $strField) {
            if ($objEvent->maxEscortsPerMember < 1) {
                return false;
            }
        }

        return true;
    }

    public static function disableHook(): void
    {
        self::$disableHook = true;
    }

    public static function enableHook(): void
    {
        self::$disableHook = false;
    }

    public static function isEnabled(): bool
    {
        return self::$disableHook;
    }
}
