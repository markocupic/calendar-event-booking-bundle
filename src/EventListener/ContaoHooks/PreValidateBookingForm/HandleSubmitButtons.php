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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\PreValidateBookingForm;

use Codefog\HasteBundle\Util\ArrayPosition;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingController;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add or hide submit buttons in the event booking form.
 */
#[AsHook(HandleSubmitButtons::HOOK, priority: 1000)]
final class HandleSubmitButtons extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function __invoke(EventBookingController $frontendModule): void
    {
        if (!self::isEnabled()) {
            return;
        }

        /*
         * Not used yet
         */
        return;

        $eventConfig = $frontendModule->eventConfig;

        if (null === ($eventRegistration = $frontendModule->getEventRegistrationHelper())) {
            return;
        }

        if (null === ($form = $eventRegistration->getForm())) {
            return;
        }

        // Add the waiting list submit button
        if (EventBookingController::CASE_WAITING_LIST_POSSIBLE === $frontendModule->case) {
            if (!$form->hasFormField('cebbBookingWaitingListSubmit')) {
                $form->addSubmitFormField($this->translator->trans('BTN.cebb_booking_waiting_list_submit_lbl', [], 'contao_default'), 'cebbBookingWaitingListSubmit', ArrayPosition::last());
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
