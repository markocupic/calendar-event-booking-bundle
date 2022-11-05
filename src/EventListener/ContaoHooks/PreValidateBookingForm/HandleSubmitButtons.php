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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\PreValidateBookingForm;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Doctrine\DBAL\Exception;
use Haste\Util\ArrayPosition;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add or hide submit buttons in the event booking form.
 *
 * @Hook(HandleSubmitButtons::HOOK, priority=HandleSubmitButtons::PRIORITY)
 */
final class HandleSubmitButtons extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM;
    public const PRIORITY = 1000;

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $frontendModule): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $eventConfig = $frontendModule->eventConfig;

        if (null === ($eventRegistration = $frontendModule->getEventRegistrationHelper())) {
            return;
        }

        if (null === ($form = $eventRegistration->getForm())) {
            return;
        }

        // Add the waiting list submit button
        if (CalendarEventBookingEventBookingModuleController::CASE_WAITING_LIST_POSSIBLE === $frontendModule->case) {
            if (!$form->hasFormField('cebbBookingWaitingListSubmit')) {
                $form->addSubmitFormField('cebbBookingWaitingListSubmit', $this->translator->trans('BTN.cebb_booking_waiting_list_submit_lbl', [], 'contao_default'), ArrayPosition::last());
            }
        }

        // Remove default submit button
        if ($eventConfig->isFullyBooked()) {
            if ($form->hasFormField('cebbBookingDefaultSubmit')) {
                $form->removeFormField('cebbBookingDefaultSubmit');
            }
        }
    }
}
