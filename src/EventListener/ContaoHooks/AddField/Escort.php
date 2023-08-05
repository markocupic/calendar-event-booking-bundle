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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AddField;

use Codefog\HasteBundle\Form\Form;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingController;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;

#[AsHook(Escort::HOOK, priority: 1000)]
final class Escort extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_ADD_FIELD;

    public function __invoke(Form $form, string $strField, array $arrDca, EventConfig $eventConfig, EventBookingController $moduleInstance): bool
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
