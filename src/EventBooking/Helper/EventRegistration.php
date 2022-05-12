<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber\EventSubscriber;
use Symfony\Component\HttpFoundation\RequestStack;

class EventRegistration
{
    public const FLASH_KEY = '_event_registration';

    private Connection $connection;
    private RequestStack $requestStack;

    public function __construct(Connection $connection, RequestStack $requestStack)
    {
        $this->connection = $connection;
        $this->requestStack = $requestStack;
    }

    /**
     * Is fully booked means:
     * - Event has no free seats
     *   and
     * - The waiting list is ignored.
     *
     * @throws Exception
     */
    public function isFullyBooked(EventConfig $eventConfig): bool
    {
        $confirmedBookingsCount = $this->getConfirmedBookingsCount($eventConfig);
        $bookingMax = $eventConfig->getBookingMax();

        if ($bookingMax > 0 && $confirmedBookingsCount >= $bookingMax) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function getConfirmedBookingsCount(EventConfig $eventConfig): int
    {
        return $this->countByEventAndBookingState(
            $eventConfig,
            BookingState::STATE_CONFIRMED,
            (bool) $eventConfig->get('addEscortsToTotal'),
        );
    }

    /**
     * @throws Exception
     */
    public function isWaitingListFull(EventConfig $eventConfig): bool
    {
        if ($eventConfig->get('activateWaitingList')) {
            if (!$eventConfig->get('waitingListLimit')) {
                return false;
            }

            if ($this->getWaitingListCount($eventConfig) < (int) $eventConfig->get('waitingListLimit')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function getWaitingListCount(EventConfig $eventConfig): int
    {
        return $this->countByEventAndBookingState(
            $eventConfig,
            BookingState::STATE_WAITING_LIST,
            (bool) $eventConfig->get('addEscortsToTotal'),
        );
    }

    /**
     * @throws Exception
     */
    public function getNotConfirmedCount(EventConfig $eventConfig): int
    {
        return $this->countByEventAndBookingState(
            $eventConfig,
            BookingState::STATE_NOT_CONFIRMED,
            (bool) $eventConfig->get('addEscortsToTotal'),
        );
    }

    /**
     * @throws Exception
     */
    public function countByEventAndBookingState(EventConfig $eventConfig, string $bookingState, bool $addEscortsToTotal = false): int
    {
        $query1 = 'SELECT COUNT(id) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?';
        $registrationCount = $this->connection->fetchOne(
            $query1,
            [$eventConfig->getModel()->id, $bookingState],
        );

        $sumBookingTotal = $registrationCount;

        if ($addEscortsToTotal) {
            $query2 = 'SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?';
            $sumEscorts = $this->connection->fetchOne($query2, [$eventConfig->getModel()->id, $bookingState]);

            if (false !== $sumEscorts) {
                $sumBookingTotal += $sumEscorts;
            }
        }

        return $sumBookingTotal;
    }

    public function addToSession(EventConfig $eventConfig, EventSubscriber $eventSubscriber): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $flashBag = $session->getFlashBag();
        $arrSession = [];

        $arrSession['eventData'] = $eventConfig->getModel()->row();
        $arrSession['memberData'] = $eventSubscriber->getModel()->row();
        $arrSession['formData'] = $eventSubscriber->getForm()->fetchAll();

        $flashBag->set(self::FLASH_KEY, $arrSession);
    }
}
