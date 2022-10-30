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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PreValidateBookingForm;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
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
            if (!$form->hasFormField('addToWaitingListSubmit')) {
                $form->addSubmitFormField('addToWaitingListSubmit', $this->translator->trans('MSC.addToWaitingListBtnLbl', [], 'contao_default'));
            }
        }

        // Remove default submit button
        if ($eventConfig->isFullyBooked()) {
            $this->removeDefaultSubmitBtn($form);
        }
    }

    private function removeDefaultSubmitBtn(Form $form): void
    {
        $arrFormFields = $form->getFormFields();

        // Retrieving default submit button form element is a bit tedious, because it gets no name attribute
        foreach ($arrFormFields as $name => $arrFormField) {
            // Exclude the waiting list submit button
            if ('addToWaitingListSubmit' === $name) {
                continue;
            }

            if (isset($arrFormField['type'])) {
                if ('submit' === $arrFormField['type']) {
                    if ($form->hasFormField($name)) {
                        $form->removeFormField($name);
                    }
                }
            }
        }
    }
}
