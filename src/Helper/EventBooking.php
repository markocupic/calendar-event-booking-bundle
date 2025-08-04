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

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Codefog\HasteBundle\UrlParser;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\FrontendUser;
use Contao\Input;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingOptInController;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingUnsubscribeController;
use Markocupic\CalendarEventBookingBundle\Event\ResolveEventStatusEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EventBooking
{
    public const FLASH_KEY = '_event_booking';

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly TokenChecker $tokenChecker,
        private readonly UrlParser $urlParser,
    ) {
    }

    public function hasLoggedInFrontendUser(): bool
    {
        return $this->tokenChecker->hasFrontendUser();
    }

    public function getLoggedInFrontendUser(): FrontendUser|null
    {
        if (!$this->tokenChecker->hasFrontendUser()) {
            return null;
        }

        return FrontendUser::getInstance();
    }

    public function getEventFromCurrentUrl(): CalendarEventsModel|null
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        // Set the item from the auto_item parameter
        if (empty($inputAdapter->get('events')) && isset($_GET['auto_item'])) {
            $inputAdapter->setGet('events', Input::get('auto_item'));
        }

        $eventIdOrAlias = $inputAdapter->get('events');

        if (!empty($eventIdOrAlias)) {
            if (null !== ($event = $calendarEventsModelAdapter->findByIdOrAlias($eventIdOrAlias))) {
                return $event;
            }
        }

        return null;
    }

    /**
     * Resolves the booking status of a calendar event based on its configuration,
     * current time, and booking conditions.
     *
     * The method evaluates the booking state of a calendar event, such as whether
     * bookings are possible, disabled, not yet started, already ended, on a waiting
     * list, or fully booked. It dispatches an event to allow for further
     * modifications to the resolved booking status.
     */
    public function resolveEventStatus(CalendarEventsModel $calendarEvent, Request $request): string
    {
        $eventStatus = (
            function ($calendarEvent): string {
                if (!$calendarEvent->published || ('' !== (string) $calendarEvent->start && $calendarEvent->start > time()) || ('' !== (string) $calendarEvent->end && $calendarEvent->end < time())) {
                    return EventStatus::DRAFT;
                }

                if (!$calendarEvent->enableBookingForm) {
                    return EventStatus::NOT_BOOKABLE;
                }

                if ($calendarEvent->bookingStartDate > time()) {
                    return EventStatus::NOT_YET_BOOKABLE;
                }

                if (is_numeric($calendarEvent->bookingEndDate) && $calendarEvent->bookingEndDate < time()) {
                    return EventStatus::BOOKING_CLOSED;
                }

                if ($this->isFullyBooked($calendarEvent)) {
                    if ($this->canFulfillBookingRequestWaitingList($calendarEvent, 1)) {
                        return EventStatus::WAITING_LIST_OPEN;
                    }

                    return EventStatus::FULLY_BOOKED;
                }

                return EventStatus::BOOKING_OPEN;
            }
        )($calendarEvent);

        $event = new ResolveEventStatusEvent($calendarEvent, $request, $eventStatus);

        $this->eventDispatcher->dispatch($event);

        return $event->getEventStatus();
    }

    /**
     * Checks if it is possible to register for a given calendar event based on its
     * booking status. Returns true if registration is possible (either directly or
     * via a waiting list).
     */
    public function canRegister(CalendarEventsModel $event, Request $request): bool
    {
        $eventStatus = $this->resolveEventStatus($event, $request);

        return \in_array($eventStatus, [EventStatus::BOOKING_OPEN, EventStatus::WAITING_LIST_OPEN], true);
    }

    /**
     * Checks whether an event is fully booked by comparing the current booking count
     * with the maximum allowed bookings for the event.
     */
    public function isFullyBooked(CalendarEventsModel $event): bool
    {
        $bookingCount = $this->getBookingCount($event);

        if ($event->maxBookings < 1) {
            return false;
        }

        if ($bookingCount >= $event->maxBookings) {
            return true;
        }

        return false;
    }

    /**
     * Checks whether the waiting list for a given event is full.
     */
    public function isWaitingListFull(CalendarEventsModel $event): bool
    {
        return !$this->canFulfillBookingRequestWaitingList($event, 1);
    }

    /**
     * Determines whether a booking request for the waiting list can be fulfilled
     * based on the event's settings and the number of requested tickets.
     */
    public function canFulfillBookingRequestWaitingList(CalendarEventsModel $event, int $requestedTickets): bool
    {
        if (!$event->enableWaitingList) {
            return false;
        }

        $hasUnlimitedWaitingList = $event->maxWaitingList < 1;

        if ($hasUnlimitedWaitingList) {
            return true;
        }

        $currentWaitingCount = $this->getWaitingListCount($event);
        $totalRequestedSpots = $currentWaitingCount + $requestedTickets;

        return $event->maxWaitingList >= $totalRequestedSpots;
    }

    /**
     * Determines if the booking request can be fulfilled based on the requested
     * tickets and the maximum allowed tickets.
     */
    public function canFulfillBookingRequest(CalendarEventsModel $event, int $requestedTickets): bool
    {
        $currentlyBookedTickets = $this->getBookingCount($event);
        $totalRequiredTickets = $currentlyBookedTickets + $requestedTickets;

        return $totalRequiredTickets <= $event->maxBookings;
    }

    public function getFreeSpotsCount(CalendarEventsModel $event): int
    {
        return max([$event->maxBookings - $this->getBookingCount($event), 0]);
    }

    /**
     * Calculates the total booking count for a calendar event, optionally including
     * the waiting list.
     */
    public function getBookingCount(CalendarEventsModel $event, bool $includeWaitingList = false): int
    {
        // Count bookings
        $memberCount = (int) $this->connection->fetchOne('
            SELECT
                SUM(ticketAmount)
            FROM
                tl_calendar_events_member
            WHERE
                pid = ?
              AND
                canceled = ?
              AND
                expired = ?
              AND
                waitingList = ?
                ',
            [$event->id, 0, 0, 0],
        );

        if ($includeWaitingList) {
            $memberCount += $this->getWaitingListCount($event);
        }

        return $memberCount;
    }

    /**
     * Retrieves the count of participants on the waiting list for a specific
     * calendar event.
     */
    public function getWaitingListCount(CalendarEventsModel $event): int
    {
        return (int) $this->connection->fetchOne('
                SELECT
                    SUM(ticketAmount)
                FROM
                    tl_calendar_events_member
                WHERE
                    pid = ?
                  AND
                    canceled = ?
                  AND
                    expired = ?
                  AND
                    waitingList = ?
                  ',
            [$event->id, 0, 0, 1],
        );
    }

    public function addToSession(CalendarEventsModel $event, CalendarEventsMemberModel $booking, Request $request): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $flashBag = $session->getFlashBag();
        $arrSession = [];

        $arrSession['eventData'] = $event->row();
        $arrSession['memberData'] = $booking->row();
        $arrSession['formData'] = $request->request->all();

        $flashBag->set(self::FLASH_KEY, $arrSession);
    }

    public function getUnsubscribeLink(CalendarEventsMemberModel $booking): string
    {
        if (null === ($event = $booking->getRelated('pid'))) {
            return '';
        }

        if (!$event->enableDeregistration) {
            return '';
        }

        if (null === ($calendar = $event->getRelated('pid'))) {
            return '';
        }

        if (null === ($page = PageModel::findById($calendar->eventUnsubscribePage))) {
            return '';
        }

        $params = \sprintf('action=%s&bookingToken=%s', EventBookingUnsubscribeController::ACTION, $booking->bookingToken);

        return $this->urlParser->addQueryString($params, $page->getAbsoluteUrl());
    }

    public function getOptInLink(CalendarEventsMemberModel $booking): string
    {
        if (null === ($event = $booking->getRelated('pid'))) {
            throw new \Exception('Event not found.');
        }

        if (null === ($calendar = $event->getRelated('pid'))) {
            throw new \Exception('Calendar not found.');
        }

        if (!$calendar->requireOptIn) {
            return '';
        }

        if (null === ($page = PageModel::findById($calendar->eventBookingOptInPage))) {
            return '';
        }

        $params = \sprintf('action=%s&bookingToken=%s', EventBookingOptInController::ACTION, $booking->bookingToken);

        return $this->urlParser->addQueryString($params, $page->getAbsoluteUrl());
    }
}
