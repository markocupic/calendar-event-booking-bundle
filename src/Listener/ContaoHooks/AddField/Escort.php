<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AddField;

use Codefog\HasteBundle\Form\Form;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;

/**
 * Codefog haste "Add field Hook".
 */
#[AsHook(Escort::HOOK, priority: 1000)]
final class Escort
{
    public const HOOK = 'calEvtBookingAddField';

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
}
