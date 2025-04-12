<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\ValidateBookingRequest;

use Codefog\HasteBundle\Form\Form;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook(ValidateEscorts::HOOK, priority: 1200)]
final class ValidateEscorts
{
    public const HOOK = 'calEvtBookingValidateBookingRequest';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EventRegistration $eventRegistration,
    ) {
    }

    /**
     * Important! return false will make the validation fail
     * Validate escorts.
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance, array $arrDisabledHooks = []): bool
    {
        if (\in_array(self::class, $arrDisabledHooks, true)) {
            return true;
        }

        /** @var Form $form */
        $form = $moduleInstance->getForm();

        /** @var CalendarEventsModel $event */
        $event = $moduleInstance->getEvent();

        if ($form->hasFormField('escorts')) {
            $widget = $form->getWidget('escorts');

            if (empty($widget->value)) {
                $widget->value = 0;
            }

            if ((int) $widget->value < 0 || (string) $widget->value !== (string) (int) ($widget->value)) {
                $errorMsg = $this->translator->trans('MSC.enterPosIntVal', [], 'contao_default');
                $widget->addError($errorMsg);
            } elseif ($this->eventRegistration->isFullyBooked($event)) {
                $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$event->maxMembers], 'contao_default');
                $widget->addError($errorMsg);
            } elseif ((int) $widget->value > 0) {
                if ((int) $widget->value > (int) $event->maxEscortsPerMember) {
                    $errorMsg = $this->translator->trans('MSC.maxEscortsPossible', [$event->maxEscortsPerMember], 'contao_default');
                    $widget->addError($errorMsg);
                }
            }

            if ($widget->hasErrors()) {
                // return false will make the validation fail
                return false;
            }
        }

        return true;
    }
}
