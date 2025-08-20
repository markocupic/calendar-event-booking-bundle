<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingFormController;
use Markocupic\CalendarEventBookingBundle\Helper\EventStatus;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PrepareFormDataListener
{
    public const HOOK = 'prepareFormData';

    public function __construct(
        private readonly Connection $connection,
        private readonly EventStatus $eventStatus,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function setTargetTable(array &$submittedData, array $labels, array $fields, Form $form, array &$files): void
    {
        if (!$form->isCalendarEventBookingForm) {
            return;
        }

        $form->storeValues = true;
        $form->targetTable = CalendarEventsMemberModel::getTable();
    }

    #[AsHook(self::HOOK, priority: 900)]
    public function validateTicketAmount(array &$submittedData, array $labels, array $fields, Form $form, array &$files): void
    {
        if (!$form->isCalendarEventBookingForm) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $event = $bookingModuleInstance->getEvent();

        $requestedTickets = 1;

        if (!empty($submittedData['ticketAmount'])) {
            $requestedTickets = (int) $submittedData['ticketAmount'];
        }

        if ($this->eventStatus->canFulfillBookingRequest($event, $this->connection, $requestedTickets)) {
            // Everything is fine, the requested tickets are available
            $bookingModuleInstance->waitingListOpen = false;

            return;
        }

        if (!$event->enableWaitingList) {
            // The event is fully booked, and the waiting list is disabled.
            if ($requestedTickets > 1) {
                $errorMsg = $this->translator->trans('ERR.not_enough_spots_reduce_ticket_amount', [], 'contao_default');
            } else {
                $errorMsg = $this->translator->trans('MSC.fully_booked', [], 'contao_default');
            }
            $form->addError($errorMsg);

            $bookingModuleInstance->waitingListOpen = false;

            return;
        }

        if (!empty($submittedData['waitingList']) && !$this->eventStatus->canFulfillBookingRequestWaitingList($event, $this->connection, $requestedTickets)) {
            if ($requestedTickets > 1) {
                // Event is fully booked, but the waiting list is not full. Reduce the ticket
                // amount to the maximum available on the waiting list.
                $errorMsg = $this->translator->trans('ERR.not_enough_spots_on_waiting_list_reduce_ticket_amount', [], 'contao_default');
                $form->addError($errorMsg);

                $bookingModuleInstance->waitingListOpen = true;

                return;
            }

            // Event is fully booked and the waiting list is full.
            $errorMsg = $this->translator->trans('MSC.event_and_waiting_list_fully_booked', [], 'contao_default');
            $form->addError($errorMsg);

            $bookingModuleInstance->waitingListOpen = false;

            return;
        }

        if (empty($submittedData['waitingList'])) {
            if ($requestedTickets > 1) {
                $errorMsg = $this->translator->trans('ERR.not_enough_spots_reduce_ticket_amount_or_book_to_waiting_list', [], 'contao_default');
            } else {
                // Event is fully booked. but booking on the waiting list is possible, but the
                // waiting list flag must be set.
                $errorMsg = $this->translator->trans('MSC.booking_on_waiting_list_possible_set_the_waiting_list_flag_please', [], 'contao_default');
            }
            $bookingModuleInstance->waitingListOpen = true;

            $form->addError($errorMsg);

            return;
        }

        // Event is fully booked! But everything is fine, the requested tickets are
        // available on the waiting list.
        $bookingModuleInstance->waitingListOpen = true;
    }
}
