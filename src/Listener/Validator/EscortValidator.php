<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\Validator;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Widget;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Helper\Formatter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validate escort input.
 *
 * @Hook(EmailValidator::HOOK)
 */
class EscortValidator
{
    public const HOOK = 'calEvtBookingValidateSubscriptionRequest';

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var EventRegistration
     */
    private $eventRegistration;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ContaoFramework $framework, EventRegistration $eventRegistration, Formatter $formatter, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->eventRegistration = $eventRegistration;
        $this->translator = $translator;
    }

    public function __invoke(Form $objForm, CalendarEventsModel $objEvent): bool
    {
        if ($objForm->hasFormField('escorts')) {
            $objWidget = $objForm->getWidget('escorts');

            if ((int) $objWidget->value < 0) {
                $errorMsg = $this->translator->trans('MSC.enterPosIntVal', [], 'contao_default');
                $objWidget->addError($errorMsg);

                return false;
            }

            if ($this->hasMaxMemberLimitExceeded($objEvent, $objWidget)) {
                $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$objEvent->maxMembers], 'contao_default');
                $objWidget->addError($errorMsg);

                return false;
            }

            if ((int) $objWidget->value > 0) {
                if ((int) $objWidget->value > (int) $objEvent->maxEscortsPerMember) {
                    $errorMsg = $this->translator->trans('MSC.maxEscortsPossible', [$objEvent->maxEscortsPerMember], 'contao_default');
                    $objWidget->addError($errorMsg);

                    return false;
                }
            }
        }

        return true;
    }

    private function hasMaxMemberLimitExceeded(CalendarEventsModel $objEvent, Widget $objWidget): bool
    {
        if ('escorts' === $objWidget->name) {
            if ((int) $objEvent->maxMembers > 0 && (int) $objWidget->value > 0 && $objEvent->includeEscortsWhenCalculatingRegCount) {
                $expSum = array_sum(
                    [
                        $this->eventRegistration->getBookingCount($objEvent),
                        1,
                        (int) $objWidget->value,
                    ]
                );

                if ($expSum > (int) $objEvent->maxMembers) {
                    return true;
                }
            }
        }

        return false;
    }
}
