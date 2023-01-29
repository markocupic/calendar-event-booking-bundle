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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\ValidateRegistration;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook(ValidateEscorts::HOOK, priority: 1200)]
final class ValidateEscorts extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_VALIDATE_REGISTRATION;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Important! return false will make the validation fail
     * Validate escorts.
     *
     * @throws \Exception
     */
    public function __invoke(EventRegistration $eventRegistration, EventConfig $eventConfig): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        $form = $eventRegistration->getForm();

        if (!$form->hasFormField('escorts')) {
            return true;
        }

        if ($eventConfig->getModel()->maxEscortsPerMember > 0) {
            $widget = $form->getWidget('escorts');

            if ((int) $widget->value < 0) {
                $errorMsg = $this->translator->trans('MSC.enter_positive_integer', [], 'contao_default');
                $widget->addError($errorMsg);
            }

            if ((int) $widget->value > (int) $eventConfig->get('maxEscortsPerMember')) {
                $errorMsg = $this->translator->trans('MSC.max_escorts_possible', [$eventConfig->get('maxEscortsPerMember')], 'contao_default');
                $widget->addError($errorMsg);
            }

            if ($widget->hasErrors()) {
                // return false will make the validation fail
                return false;
            }
        }

        return true;
    }
}
