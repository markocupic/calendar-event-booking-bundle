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

namespace Markocupic\CalendarEventBookingBundle\Domain\Booking;

use Contao\CalendarEventsModel;
use Doctrine\DBAL\Connection;

/**
 * Answers capacity questions for a calendar event: how many bookings exist, how
 * many spots are free, whether the event or its waiting list is full.
 */
class BookingCapacity
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * An event without a booking limit has unlimited capacity.
     *
     * maxBookings = 0 means "no limit", the same convention maxWaitingList uses.
     *
     * Careful: getFreeSpotsCount() returns 0 for such an event as well, because
     * there is no meaningful number of remaining spots to report. Ask this method
     * first before rendering a "x spots left" counter.
     */
    public function hasUnlimitedCapacity(CalendarEventsModel $event): bool
    {
        return $event->maxBookings < 1;
    }

    /**
     * Checks whether an event is fully booked by comparing the current booking count
     * with the maximum allowed bookings for the event.
     */
    public function isFullyBooked(CalendarEventsModel $event): bool
    {
        if ($this->hasUnlimitedCapacity($event)) {
            return false;
        }

        return $this->getBookingCount($event) >= $event->maxBookings;
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
        if ($this->hasUnlimitedCapacity($event)) {
            return true;
        }

        $currentlyBookedTickets = $this->getBookingCount($event);
        $totalRequiredTickets = $currentlyBookedTickets + $requestedTickets;

        return $totalRequiredTickets <= $event->maxBookings;
    }

    /**
     * Number of spots still available.
     *
     * Returns 0 for an event with unlimited capacity too - see
     * hasUnlimitedCapacity(), which you should check before showing this figure.
     */
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
}
