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
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

class ValidateFormField
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param $formId
     */
    public function validateFormField(Widget $objWidget, $formId, array $arrForm, Form $objForm): Widget
    {
        if ($objForm->isCalendarEventBookingForm) {
            /** @var CalendarEventsModel $calendarEventsModel */
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            /** @var CalendarEventsMemberModel $calendarEventsMemberModel */
            $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);

            /** @var Input $inputAdapter */
            $inputAdapter = $this->framework->getAdapter(Input::class);

            // Do not auto save anything to the database, this will be done manualy in the processFormData method
            $objForm->storeValues = '';

            // Check if user with submitted email has already booked
            if ('email' === $objWidget->name) {
                if (!empty($objWidget->value)) {
                    $objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events'));

                    if (null !== $objEvent) {
                        if (!$objEvent->enableMultiBookingWithSameAddress) {
                            $arrOptions = [
                                'column' => ['tl_calendar_events_member.email=?', 'tl_calendar_events_member.pid=?'],
                                'value' => [strtolower($objWidget->value), $objEvent->id],
                            ];
                            $objMember = $calendarEventsMemberModelAdapter->findAll($arrOptions);

                            if (null !== $objMember) {
                                $errorMsg = sprintf($GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'], strtolower($inputAdapter->post('email')));
                                $objWidget->addError($errorMsg);
                            }
                        }
                    }
                }
            }

            // Check maxEscortsPerMember
            if ('escorts' === $objWidget->name) {
                if ($objWidget->value < 0) {
                    $errorMsg = sprintf($GLOBALS['TL_LANG']['MSC']['enterPosIntVal']);
                    $objWidget->addError($errorMsg);
                } elseif ($objWidget->value > 0) {
                    $objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events'));

                    if (null !== $objEvent) {
                        if ($objWidget->value > $objEvent->maxEscortsPerMember) {
                            $errorMsg = sprintf($GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'], $objEvent->maxEscortsPerMember);
                            $objWidget->addError($errorMsg);
                        }
                    }
                }
            }
        }

        return $objWidget;
    }
}
