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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PreValidateBookingForm;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add or hide submit buttons.
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

        if (null === ($eventSubscriber = $frontendModule->getEventSubscriber())) {
            return;
        }

        if (null === ($form = $eventSubscriber->getForm())) {
            return;
        }

        if ($eventConfig->isFullyBooked($eventConfig)) {
            $this->removeDefaultSubmit($form);
        }

        if (CalendarEventBookingEventBookingModuleController::CASE_WAITING_LIST_POSSIBLE === $frontendModule->case) {
            $form->addSubmitFormField('addToWaitingListSubmit', $this->translator->trans('MSC.addToWaitingList', [], 'contao_default'));
        }
    }

    private function removeDefaultSubmit($form): void
    {
        $arrFormFields = $form->getFormFields();

        foreach ($arrFormFields as $name => $arrFormField) {
            if (isset($arrFormField['type'])) {
                if ('submit' === $arrFormField['type'] && empty($arrFormField['name'])) {
                    $form->removeFormField($name);
                }
            }
        }
    }
}
