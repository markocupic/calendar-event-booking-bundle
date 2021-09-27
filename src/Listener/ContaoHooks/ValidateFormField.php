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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Form;
use Contao\Input;
use Contao\Widget;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidateFormField
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var EventRegistration
     */
    private $eventRegistration;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(ContaoFramework $framework, EventRegistration $eventRegistration, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->eventRegistration = $eventRegistration;
        $this->translator = $translator;
    }

    /**
     * @param $formId
     */
    public function validateFormField(Widget $objWidget, $formId, array $arrForm, Form $objForm): Widget
    {
        if ($objForm->isCalendarEventBookingForm) {
            /** @var CalendarEventsMemberModel $calendarEventsMemberModel */
            $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);

            /** @var Input $inputAdapter */
            $inputAdapter = $this->framework->getAdapter(Input::class);

            // Do not auto save anything to the database, this will be done manualy in the processFormData method
            $objForm->storeValues = '';

            // Check if user with submitted email has already booked
            if ('email' === $objWidget->name) {
                if (!empty($objWidget->value)) {
                    $objEvent = $this->eventRegistration->getCurrentEventFromUrl();

                    if (null !== $objEvent) {
                        if (!$objEvent->enableMultiBookingWithSameAddress) {
                            $arrOptions = [
                                'column' => ['tl_calendar_events_member.email=?', 'tl_calendar_events_member.pid=?'],
                                'value' => [strtolower($objWidget->value), $objEvent->id],
                            ];
                            $objMember = $calendarEventsMemberModelAdapter->findAll($arrOptions);

                            if (null !== $objMember) {
                                $errorMsg = $this->translator->trans('MSC.youHaveAlreadyBooked', [$inputAdapter->post('email')], 'contao_default');
                                $objWidget->addError($errorMsg);
                            }
                        }
                    }
                }
            }

            // Check maxEscortsPerMember
            if ('escorts' === $objWidget->name) {
                $objEvent = $this->eventRegistration->getCurrentEventFromUrl();

                if (null !== $objEvent) {
                    if ((int) $objWidget->value < 0) {
                        $errorMsg = $this->translator->trans('MSC.enterPosIntVal', [], 'contao_default');
                        $objWidget->addError($errorMsg);
                    } elseif ($this->hasMaxMemberLimitExceeded($objEvent, $objWidget)) {
                        $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$objEvent->maxMembers], 'contao_default');
                        $objWidget->addError($errorMsg);
                    } elseif ((int) $objWidget->value > 0) {
                        if ((int) $objWidget->value > (int) $objEvent->maxEscortsPerMember) {
                            $errorMsg = $this->translator->trans('MSC.maxEscortsPossible', [$objEvent->maxEscortsPerMember], 'contao_default');
                            $objWidget->addError($errorMsg);
                        }
                    }
                }
            }
        }

        return $objWidget;
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
