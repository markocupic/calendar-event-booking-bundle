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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ValidateBookingRequest;

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

        /** @var Form $objForm */
        $objForm = $moduleInstance->getProperty('objForm');

        /** @var CalendarEventsModel $objEvent */
        $objEvent = $moduleInstance->getProperty('objEvent');

        if ($objForm->hasFormField('escorts')) {
            $objWidget = $objForm->getWidget('escorts');

            if ((int) $objWidget->value < 0) {
                $errorMsg = $this->translator->trans('MSC.enterPosIntVal', [], 'contao_default');
                $objWidget->addError($errorMsg);
            } elseif ($this->eventRegistration->isFullyBooked($objEvent)) {
                $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$objEvent->maxMembers], 'contao_default');
                $objWidget->addError($errorMsg);
            } elseif ((int) $objWidget->value > 0) {
                if ((int) $objWidget->value > (int) $objEvent->maxEscortsPerMember) {
                    $errorMsg = $this->translator->trans('MSC.maxEscortsPossible', [$objEvent->maxEscortsPerMember], 'contao_default');
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
}
