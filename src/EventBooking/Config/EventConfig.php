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

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Config;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\Input;
use Contao\Model\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

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

    public static function getEventFromCurrentUrl(): ?CalendarEventsModel
    {
        // Set the item from the auto_item parameter
        if (!isset($_GET['events']) && Config::get('useAutoItem') && isset($_GET['auto_item'])) {
            Input::setGet('events', Input::get('auto_item'));
        }

        $eventIdOrAlias = Input::get('events');

        // Return an empty string if "events" is not set
        if ('' !== $eventIdOrAlias) {
            if (null !== ($objEvent = CalendarEventsModel::findByIdOrAlias($eventIdOrAlias))) {
                return $objEvent;
            }
        }

        return null;
    }

    public function hasWaitingList(): bool
    {
        return (bool) $this->get('activateWaitingList');
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

    /**
     * @throws Exception
     */
    public function isWaitingListFull(): bool
    {
        if ($this->get('activateWaitingList')) {
            if (!$this->get('waitingListLimit')) {
                return false;
            }

            if ($this->getWaitingListCount() < (int) $this->get('waitingListLimit')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function getWaitingListCount(): int
    {
        return $this->countByEventAndBookingState(
            BookingState::STATE_WAITING_LIST,
            (bool) $this->get('addEscortsToTotal'),
        );
    }

    /**
     * @throws Exception
     */
    private function countByEventAndBookingState(string $bookingState, bool $addEscortsToTotal = false): int
    {
        $query1 = 'SELECT COUNT(id) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?';
        $registrationCount = $this->connection->fetchOne(
            $query1,
            [$this->getModel()->id, $bookingState],
        );

        $sumBookingTotal = $registrationCount;

        if ($addEscortsToTotal) {
            $query2 = 'SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?';
            $sumEscorts = $this->connection->fetchOne($query2, [$this->getModel()->id, $bookingState]);

            if (false !== $sumEscorts) {
                $sumBookingTotal += $sumEscorts;
            }
        }

        return $sumBookingTotal;
    }

    /**
     * Is fully booked means:
     * - Event has no free seats
     *   and
     * - The waiting list is ignored.
     *
     * @throws Exception
     */
    public function isFullyBooked(): bool
    {
        $confirmedBookingsCount = $this->getConfirmedBookingsCount();
        $bookingMax = $this->getBookingMax();

        if ($bookingMax > 0 && $confirmedBookingsCount >= $bookingMax) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function getConfirmedBookingsCount(): int
    {
        return $this->countByEventAndBookingState(
            BookingState::STATE_CONFIRMED,
            (bool) $this->get('addEscortsToTotal'),
        );
    }

    public function getBookingMax(): int
    {
        return (int) $this->get('maxMembers');
    }

    /**
     * @throws Exception
     */
    public function getNotConfirmedCount(): int
    {
        return $this->countByEventAndBookingState(
            BookingState::STATE_NOT_CONFIRMED,
            (bool) $this->get('addEscortsToTotal'),
        );
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function getNumberOfFreeSeats(bool $blnWaitingList = false): int
    {
        if (!$this->isBookable()) {
            return 0;
        }

        $startTstamp = empty($this->event->bookingStartDate) ? 0 : (int) $this->event->bookingStartDate;
        $endTstamp = empty($this->event->bookingEndDate) ? 0 : (int) $this->event->bookingEndDate;

        if ($startTstamp > time() || time() > $endTstamp) {
            return 0;
        }

        if (!$blnWaitingList) {
            $total = $this->getConfirmedBookingsCount();
            $available = $this->getBookingMax() - $total;
        } else {
            if (!$this->getWaitingListLimit()) {
                throw new \Exception('The waiting list has no member limit. Please correct this in your event settings.');
            }

            $total = $this->getWaitingListCount();
            $available = $this->getWaitingListLimit() - $total;
        }

        return max($available, 0);
    }

    public function isBookable(): bool
    {
        return (bool) $this->get('activateBookingForm');
    }

    public function getWaitingListLimit(): int
    {
        return (int) $this->event->waitingListLimit;
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

    public function getBookingMin(): int
    {
        return (int) $this->get('minMembers');
    }

    public function getRegistrationsAsArray(array $arrBookingStateFilter = []): ?array
    {
        $arrReg = [];

        if (null !== ($collection = $this->getRegistrations($arrBookingStateFilter))) {
            while ($collection->next()) {
                $arrReg[] = $collection->row();
            }
        }

        return !empty($arrReg) ? $arrReg : null;
    }

    public function getRegistrations(array $arrBookingStateFilter = []): ?Collection
    {
        $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);

        if (empty($arrBookingStateFilter)) {
            return $calendarEventsMemberModelAdapter->findByPid($this->getModel()->id);
        }

        $collection = [];
        $registrations = $calendarEventsMemberModelAdapter->findByPid($this->getModel()->id);

        if (null === $registrations) {
            return null;
        }

        while ($registrations->next()) {
            if (\in_array($registrations->bookingState, $arrBookingStateFilter, true)) {
                $collection[] = $registrations->current();
            }
        }

        return !empty($collection) ? new Collection($collection, $calendarEventsMemberModelAdapter->getTable()) : null;
    }
}
