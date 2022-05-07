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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ValidateRegistration;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController as BookingModule;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook(ValidateEscorts::HOOK, priority=ValidateEscorts::PRIORITY)
 */
final class ValidateEscorts extends AbstractHook
{
    public const HOOK = 'calEvtBookingValidateRegistration';
    public const PRIORITY = 1200;

    private TranslatorInterface $translator;
    private EventRegistration $eventRegistration;

    public function __construct(TranslatorInterface $translator, EventRegistration $eventRegistration)
    {
        $this->translator = $translator;
        $this->eventRegistration = $eventRegistration;
    }

    /**
     * Important! return false will make the validation fail
     * Validate escorts.
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        /** @var Form $objForm */
        $objForm = $moduleInstance->getProperty('objForm');

        /** @var EventConfig $eventConfig */
        $eventConfig = $moduleInstance->getProperty('eventConfig');

        if ($objForm->hasFormField('escorts')) {
            $objWidget = $objForm->getWidget('escorts');

            if ((int) $objWidget->value < 0) {
                $errorMsg = $this->translator->trans('MSC.enterPosIntVal', [], 'contao_default');
                $objWidget->addError($errorMsg);
            } elseif ($this->waitingListExceeded($moduleInstance)) {
            } elseif ($this->eventRegistration->isFullyBooked($eventConfig) && BookingModule::CASE_WAITING_LIST_POSSIBLE !== $moduleInstance->case) {
                $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$eventConfig->get('maxMembers')], 'contao_default');
                $objWidget->addError($errorMsg);
            } elseif ((int) $objWidget->value > 0) {
                if ((int) $objWidget->value > (int) $eventConfig->get('maxEscortsPerMember')) {
                    $errorMsg = $this->translator->trans('MSC.maxEscortsPossible', [$eventConfig->get('maxEscortsPerMember')], 'contao_default');
                    $objWidget->addError($errorMsg);
                }
            }

            if ($objWidget->hasErrors()) {
                // return false will make the validation fail
                return false;
            }
        }

        return true;
    }

    private function waitingListExceeded($frontendModule): bool
    {
        if (BookingModule::CASE_WAITING_LIST_POSSIBLE === $frontendModule->case) {
            return true;
        }

        return false;
    }
}
