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
}
