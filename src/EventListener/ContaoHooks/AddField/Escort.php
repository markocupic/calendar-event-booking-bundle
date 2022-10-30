<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AddField;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;

/**
 * @Hook(Escort::HOOK, priority=Escort::PRIORITY)
 */
final class Escort extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_ADD_FIELD;
    public const PRIORITY = 1000;

    public function __invoke(Form $form, string $strField, array $arrDca, EventConfig $eventConfig, CalendarEventBookingEventBookingModuleController $moduleInstance): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        // Skip input field "escorts" if escorts are not allowed
        if ('escorts' === $strField) {
            if ((int) $eventConfig->get('maxEscortsPerMember') < 1) {
                return false;
            }
        }

        return true;
    }
}
