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

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Config;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;

class EventConfig
{
    private ContaoFramework $framework;
    private Connection $connection;
    private CalendarEventsModel $event;

    // Adapters
    private Adapter $config;
    private Adapter $date;

    public function __construct(ContaoFramework $framework, Connection $connection, CalendarEventsModel $event)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->event = $event;

        // Adapters
        $this->config = $this->framework->getAdapter(Config::class);
        $this->date = $this->framework->getAdapter(Date::class);
    }

    public function get($propertyName)
    {
        // @todo enable presetting values in tl_calendar
        return $this->getModel()->$propertyName;
    }

    public function getModel(): CalendarEventsModel
    {
        return $this->event;
    }

    public function isBookable(): bool
    {
        return (bool) $this->get('activateBookingForm');
    }

    public function hasWaitingList(): bool
    {
        return (bool) $this->get('activateWaitingList');
    }

    /**
     * @throws Exception
     */
    public function isWaitingListFull(self $eventConfig): bool
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

    public function getWaitingListLimit(): int
    {
        return (int) $this->event->waitingListLimit;
    }

    /**
     * @throws Exception
     */
    public function getWaitingListCount(self $eventConfig): int
    {
        return $this->countByEventAndBookingState(
            $eventConfig,
            BookingState::STATE_WAITING_LIST,
            (bool) $eventConfig->get('addEscortsToTotal'),
        );
    }

    /**
     * Is fully booked means:
     * - Event has no free seats
     *   and
     * - The waiting list is ignored.
     *
     * @throws Exception
     */
    public function isFullyBooked(self $eventConfig): bool
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
    public function getConfirmedBookingsCount(self $eventConfig): int
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
    public function getNotConfirmedCount(self $eventConfig): int
    {
        return $this->countByEventAndBookingState(
            $eventConfig,
            BookingState::STATE_NOT_CONFIRMED,
            (bool) $eventConfig->get('addEscortsToTotal'),
        );
    }

    public function isNotificationActivated(): bool
    {
        return (bool) $this->get('activateBookingNotification');
    }

    public function getBookingStartDate(string $format = 'timestamp'): string
    {
        $tstamp = empty($this->event->bookingStartDate) ? 0 : (int) $this->event->bookingStartDate;

        if ('timestamp' === $format) {
            $varValue = (string) $tstamp;
        } elseif ('date' === $format) {
            $varValue = $this->date->parse($this->config->get('dateFormat'), $tstamp);
        } elseif ('datim' === $format) {
            $varValue = $this->date->parse($this->config->get('datimFormat'), $tstamp);
        } else {
            $varValue = (string) $tstamp;
        }

        return $varValue;
    }

    public function getBookingEndDate(string $format = 'timestamp'): string
    {
        $tstamp = empty($this->event->bookingEndDate) ? 0 : (int) $this->event->bookingEndDate;

        if ('timestamp' === $format) {
            $varValue = (string) $tstamp;
        } elseif ('date' === $format) {
            $varValue = $this->date->parse($this->config->get('dateFormat'), $tstamp);
        } elseif ('datim' === $format) {
            $varValue = $this->date->parse($this->config->get('datimFormat'), $tstamp);
        } else {
            $varValue = (string) $tstamp;
        }

        return $varValue;
    }

    public function getBookingMax(): int
    {
        return (int) $this->get('maxMembers');
    }

    public function getBookingMin(): int
    {
        return (int) $this->get('minMembers');
    }

    public static function getEventFromCurrentUrl(): ?CalendarEventsModel
    {
        // Set the item from the auto_item parameter
        if (!isset($_GET['events']) && Config::get('useAutoItem') && isset($_GET['auto_item'])) {
            Input::setGet('events', Input::get('auto_item'));
        }

        // Return an empty string if "events" is not set
        if ('' !== Input::get('events')) {
            if (null !== ($objEvent = CalendarEventsModel::findByIdOrAlias(Input::get('events')))) {
                return $objEvent;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function countByEventAndBookingState(self $eventConfig, string $bookingState, bool $addEscortsToTotal = false): int
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
}
