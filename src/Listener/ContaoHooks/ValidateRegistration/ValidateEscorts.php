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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ValidateRegistration;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook(ValidateEscorts::HOOK, priority=ValidateEscorts::PRIORITY)
 */
final class ValidateEscorts extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_VALIDATE_REGISTRATION;
    public const PRIORITY = 1200;

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Important! return false will make the validation fail
     * Validate escorts.
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
                $errorMsg = $this->translator->trans('MSC.enterPosIntVal', [], 'contao_default');
                $widget->addError($errorMsg);
            }

            if ((int) $widget->value > (int) $eventConfig->get('maxEscortsPerMember')) {
                $errorMsg = $this->translator->trans('MSC.maxEscortsPossible', [$eventConfig->get('maxEscortsPerMember')], 'contao_default');
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
