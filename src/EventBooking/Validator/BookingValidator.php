<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Validator;

use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;

class BookingValidator
{
    public const FLASH_KEY = '_event_registration';

    /**
     * @throws Exception
     */
    public function validateBookingMax(EventConfig $eventConfig, int $numSeats = 1): bool
    {
        if (!$eventConfig->isBookable()) {
            return false;
        }

        // Value is not set, unlimited number of subscriptions
        if (!($seatsAvailable = $eventConfig->getBookingMax())) {
            return true;
        }

        $total = $eventConfig->getConfirmedBookingsCount();

        return !($total + $numSeats > $seatsAvailable);
    }

    /**
     * @throws Exception
     */
    public function validateBookingMaxWaitingList(EventConfig $eventConfig, int $numSeats = 1): bool
    {
        if (!$eventConfig->isBookable()) {
            return false;
        }

        if (!$eventConfig->hasWaitingList()) {
            return false;
        }

        // Value is not set, unlimited number of subscriptions
        if (!($seatsAvailable = $eventConfig->getWaitingListLimit())) {
            return true;
        }

        $total = $eventConfig->getWaitingListCount();

        return !($total + $numSeats > $seatsAvailable);
    }

    public function validateBookingStartDate(EventConfig $eventConfig): bool
    {
        if (!$eventConfig->isBookable() || $eventConfig->getModel()->bookingStartDate > time()) {
            return false;
        }

        return true;
    }

    public function validateBookingEndDate(EventConfig $eventConfig): bool
    {
        if (!$eventConfig->isBookable() || !is_numeric($eventConfig->getModel()->bookingEndDate) || $eventConfig->getModel()->bookingEndDate < time()) {
            return false;
        }

        return true;
    }

    /**
     * Validate if:
     * - Event is bookable
     *   and
     * - Event is not fully booked
     *   or
     * - Event is fully booked, but subscribing to the waiting list is still possible.
     *
     * @throws Exception
     */
    public function validateCanRegister(EventConfig $eventConfig): bool
    {
        if (!$eventConfig->isBookable()) {
            return false;
        }

        if (!$this->validateBookingStartDate($eventConfig)) {
            return false;
        }

        if (!$this->validateBookingEndDate($eventConfig)) {
            return false;
        }

        if ($this->validateBookingMax($eventConfig, 1)) {
            return true;
        }

        if ($this->validateBookingMaxWaitingList($eventConfig, 1)) {
            return true;
        }

        return false;
    }
}
