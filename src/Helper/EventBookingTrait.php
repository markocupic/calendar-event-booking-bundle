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

trait EventBookingTrait
{
    /**
     * Checks whether an event is fully booked by comparing the current booking count
     * with the maximum allowed bookings for the event.
     */
    public function isFullyBooked(CalendarEventsModel $event, Connection $connection): bool
    {
        $bookingCount = $this->getBookingCount($event, $connection);

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
    public function isWaitingListFull(CalendarEventsModel $event, Connection $connection): bool
    {
        return !$this->canFulfillBookingRequestWaitingList($event, $connection, 1);
    }

    /**
     * Determines whether a booking request for the waiting list can be fulfilled
     * based on the event's settings and the number of requested tickets.
     */
    public function canFulfillBookingRequestWaitingList(CalendarEventsModel $event, Connection $connection, int $requestedTickets): bool
    {
        if (!$event->enableWaitingList) {
            return false;
        }

        $hasUnlimitedWaitingList = $event->maxWaitingList < 1;

        if ($hasUnlimitedWaitingList) {
            return true;
        }

        $currentWaitingCount = $this->getWaitingListCount($event, $connection);
        $totalRequestedSpots = $currentWaitingCount + $requestedTickets;

        return $event->maxWaitingList >= $totalRequestedSpots;
    }

    /**
     * Determines if the booking request can be fulfilled based on the requested
     * tickets and the maximum allowed tickets.
     */
    public function canFulfillBookingRequest(CalendarEventsModel $event, Connection $connection, int $requestedTickets): bool
    {
        $currentlyBookedTickets = $this->getBookingCount($event, $connection);
        $totalRequiredTickets = $currentlyBookedTickets + $requestedTickets;

        return $totalRequiredTickets <= $event->maxBookings;
    }

    public function getFreeSpotsCount(CalendarEventsModel $event, Connection $connection): int
    {
        return max([$event->maxBookings - $this->getBookingCount($event, $connection), 0]);
    }

    /**
     * Calculates the total booking count for a calendar event, optionally including
     * the waiting list.
     */
    public function getBookingCount(CalendarEventsModel $event, Connection $connection, bool $includeWaitingList = false): int
    {
        // Count bookings
        $memberCount = (int) $connection->fetchOne('
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
            $memberCount += $this->getWaitingListCount($event, $connection);
        }

        return $memberCount;
    }

    /**
     * Retrieves the count of participants on the waiting list for a specific
     * calendar event.
     */
    public function getWaitingListCount(CalendarEventsModel $event, Connection $connection): int
    {
        return (int) $connection->fetchOne('
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
