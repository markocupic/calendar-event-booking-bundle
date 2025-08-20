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

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Event\ResolveEventStatusEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class EventStatus
{
    use EventBookingTrait;

    public const DRAFT = 'draft'; // Event is being created but not yet visible or bookable.

    public const BOOKING_OPEN = 'booking_open'; // Users can register or buy tickets.

    public const FULLY_BOOKED = 'fully_booked'; // All spots are taken; booking no more allowed.

    public const WAITING_LIST_OPEN = 'waiting_list_open'; // Event is full, but users can join a waiting list.

    public const NOT_BOOKABLE = 'not_bookable'; // Event is not bookable.

    public const NOT_YET_BOOKABLE = 'not_yet_bookable'; // Event is not yet bookable. Waiting for the booking period to start.

    public const BOOKING_CLOSED = 'booking_closed'; // Booking period has ended (manually or automatically).

    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
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
        $eventStatus = $this->determineEventStatus($calendarEvent);
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

        return \in_array($eventStatus, [self::BOOKING_OPEN, self::WAITING_LIST_OPEN], true);
    }

    protected function determineEventStatus(CalendarEventsModel $calendarEvent): string
    {
        if (!$calendarEvent->published) {
            return self::DRAFT;
        }

        // Is the event not yet published?
        if ('' !== (string) $calendarEvent->start && $calendarEvent->start > time()) {
            return self::DRAFT;
        }

        // Is the event no more published?
        if ('' !== (string) $calendarEvent->end && $calendarEvent->end < time()) {
            return self::DRAFT;
        }

        // Is booking disabled?
        if (!$calendarEvent->enableBookingForm) {
            return self::NOT_BOOKABLE;
        }

        // Is the event not started yet?
        if ($calendarEvent->bookingStartDate > time()) {
            return self::NOT_YET_BOOKABLE;
        }

        // Has the booking period ended?
        if (is_numeric($calendarEvent->bookingEndDate) && $calendarEvent->bookingEndDate < time()) {
            return self::BOOKING_CLOSED;
        }

        if ($this->isFullyBooked($calendarEvent, $this->connection)) {
            if ($this->canFulfillBookingRequestWaitingList($calendarEvent, $this->connection, 1)) {
                return self::WAITING_LIST_OPEN;
            }

            return self::FULLY_BOOKED;
        }

        return self::BOOKING_OPEN;
    }
}
