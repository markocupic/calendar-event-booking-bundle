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

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook(Escort::HOOK, priority=Escort::PRIORITY)
 */
final class Escort extends AbstractHook
{
    public const HOOK = 'calEvtBookingAddField';
    public const PRIORITY = 1000;

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(Form $objForm, string $strField, array $arrDca, EventConfig $eventConfig, CalendarEventBookingEventBookingModuleController $moduleInstance): bool
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
