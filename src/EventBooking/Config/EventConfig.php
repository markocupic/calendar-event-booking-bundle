<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Config;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\Date;
use Contao\Input;
use Contao\Model\Collection;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingController;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;
use Markocupic\CalendarEventBookingBundle\Exception\CalendarNotFoundException;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

class EventConfig
{
    private Adapter $config;
    private Adapter $date;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly BookingValidator $bookingValidator,
        private readonly CalendarEventsModel $event,
    ) {
        $this->config = $this->framework->getAdapter(Config::class);
        $this->date = $this->framework->getAdapter(Date::class);
    }

    public static function getEventFromRequest(): CalendarEventsModel|null
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

    /**
     * @return mixed|null
     */
    public function get(string $propertyName): mixed
    {
        Controller::loadDataContainer('tl_calendar_events');
        Controller::loadDataContainer('tl_calendar');

        $arrEventFields = $GLOBALS['TL_DCA']['tl_calendar_events']['fields'] ?? [];
        $arrCalFields = $GLOBALS['TL_DCA']['tl_calendar']['fields'] ?? [];
        $inheritFromCal = $arrEventFields[$propertyName]['eval']['inheritFromCal'] ?? false;

        if (true === $inheritFromCal && !empty($arrCalFields[$propertyName])) {
            $calendar = CalendarModel::findByPk($this->get('pid'));

            if (null !== $calendar) {
                if (Database::getInstance()->fieldExists($propertyName, 'tl_calendar')) {
                    return $calendar->{$propertyName};
                }
            }
        }

        return $this->getModel()->{$propertyName};
    }

    public function getModel(): CalendarEventsModel
    {
        return $this->event;
    }

    public function getCalendar(): CalendarModel|null
    {
        return CalendarModel::findByPk($this->getModel()->pid);
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
     * Is fully booked means:
     * - Event has no free seats
     *   and
     * - The waiting list is ignored.
     *
     * @throws Exception
     */
    public function isFullyBooked(): bool
    {
        $calendar = $this->getCalendar();

        if (null === $calendar) {
            throw new CalendarNotFoundException('Can not find a matching calendar for event with ID '.$this->getModel()->id.'.');
        }

        $bookingStates = StringUtil::deserialize($calendar->calculateTotalFrom, true);
        $memberCount = 0;

        foreach ($bookingStates as $bookingState) {
            $memberCount += $this->countByEventAndBookingState($bookingState, (bool) $this->get('addEscortsToTotal'));
        }

        $bookingMax = $this->getBookingMax();

        if ($bookingMax > 0 && $memberCount >= $bookingMax) {
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
            $total = $this->getWaitingListCount();
            $available = $this->getWaitingListLimit() - $total;
        }

        return max($available, 0);
    }

    public function isBookable(): bool
    {
        return (bool) $this->get('enableBookingForm');
    }

    public function getWaitingListLimit(): int
    {
        return (int) $this->get('waitingListLimit');
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

    public function getRegistrationsAsArray(array $arrBookingStateFilter = [], array $arrOptions = []): array|null
    {
        $arrReg = [];

        if (null !== ($collection = $this->getRegistrations($arrBookingStateFilter, $arrOptions))) {
            while ($collection->next()) {
                $arrReg[] = $collection->row();
            }
        }

        return !empty($arrReg) ? $arrReg : null;
    }

    public function getRegistrations(array $arrBookingStateFilter = [], array $arrOptions = []): Collection|null
    {
        $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);

        if (empty($arrBookingStateFilter)) {
            return $calendarEventsMemberModelAdapter->findByPid($this->getModel()->id);
        }

        $t = $calendarEventsMemberModelAdapter->getTable();

        $arrColumns = [
            $t.'.pid = ?',
            $t.'.bookingState IN('.implode(',', array_fill(0, \count($arrBookingStateFilter), '?')).')',
        ];

        $arrValues = [
            $this->getModel()->id,
            ...$arrBookingStateFilter,
        ];

        return $calendarEventsMemberModelAdapter->findBy($arrColumns, $arrValues, $arrOptions);
    }

    public function getEventStatus(): string
    {
        if (!$this->isBookable()) {
            $status = EventBookingController::CASE_EVENT_NOT_BOOKABLE;
        } elseif (!$this->bookingValidator->validateBookingStartDate($this)) {
            $status = EventBookingController::CASE_BOOKING_NOT_YET_POSSIBLE;
        } elseif (!$this->bookingValidator->validateBookingEndDate($this)) {
            $status = EventBookingController::CASE_BOOKING_NO_LONGER_POSSIBLE;
        } elseif ($this->bookingValidator->validateBookingMax($this, 1)) {
            $status = EventBookingController::CASE_BOOKING_POSSIBLE;
        } elseif ($this->bookingValidator->validateBookingMaxWaitingList($this, 1)) {
            $status = EventBookingController::CASE_WAITING_LIST_POSSIBLE;
        } else {
            $status = EventBookingController::CASE_EVENT_FULLY_BOOKED;
        }

        return $status;
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

        $sumBookingTotal = (int) $registrationCount;

        if ($addEscortsToTotal) {
            $query2 = 'SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?';
            $sumEscorts = $this->connection->fetchOne($query2, [$this->getModel()->id, $bookingState]);

            if (false !== $sumEscorts) {
                $sumBookingTotal += (int) $sumEscorts;
            }
        }

        return $sumBookingTotal;
    }
}
