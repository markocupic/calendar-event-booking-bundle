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

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\FrontendUser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class EventRegistration
{
    public const FLASH_KEY = '_event_registration';

    protected ContaoFramework $framework;
    private Connection $connection;
    private Security $security;
    private RequestStack $requestStack;

    public function __construct(ContaoFramework $framework, Connection $connection, Security $security, RequestStack $requestStack)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    public function hasLoggedInFrontendUser(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof FrontendUser;
    }

    public function getLoggedInFrontendUser(): ?FrontendUser
    {
        $user = $this->security->getUser();

        if ($user instanceof FrontendUser) {
            return $user;
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getRegistrationCase(?EventConfig $eventConfig): string
    {
        if (!$eventConfig->get('activateBookingForm')) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_FORM_DISABLED;
        } elseif ($eventConfig->event->bookingStartDate > time()) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NOT_YET_POSSIBLE;
        } elseif (is_numeric($eventConfig->event->bookingEndDate) && $eventConfig->event->bookingEndDate < time()) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NO_LONGER_POSSIBLE;
        } elseif ($this->isFullyBooked($eventConfig) && !$this->isWaitingListFull($eventConfig)) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_WAITING_LIST_POSSIBLE;
        } elseif ($this->isFullyBooked($eventConfig)) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_EVENT_FULLY_BOOKED;
        } else {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_POSSIBLE;
        }

        return $state;
    }

    /**
     * @throws Exception
     */
    public function canRegister(EventConfig $eventConfig): bool
    {
        return CalendarEventBookingEventBookingModuleController::CASE_BOOKING_POSSIBLE === $this->getRegistrationCase($eventConfig);
    }

    /**
     * @throws Exception
     */
    public function isFullyBooked(EventConfig $eventConfig): bool
    {
        $bookingCount = $this->getBookingCount($eventConfig);
        $bookingMax = $eventConfig->getBookingMax();

        if ($bookingMax > 0 && $bookingCount >= $bookingMax) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function getBookingCount(EventConfig $eventConfig): int
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
    public function canAddToWaitingList(EventConfig $eventConfig, int $numEscorts = 0): bool
    {
        if ($eventConfig->get('activateWaitingList')) {
            if (!$eventConfig->get('waitingListLimit')) {
                return true;
            }

            $total = $this->getWaitingListCount($eventConfig) + 1;

            if ($eventConfig->get('addEscortsToTotal')) {
                $total += $numEscorts;
            }

            if ($total <= (int) $eventConfig->get('waitingListLimit')) {
                return true;
            }
        }

        return false;
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
    public function getWaitingForResponseCount(EventConfig $eventConfig): int
    {
        return $this->countByEventAndBookingState(
            $eventConfig,
            BookingState::STATE_WAITING_FOR_RESPONSE,
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
            [$eventConfig->event->id, $bookingState],
        );

        $sumBookingTotal = $registrationCount;

        if ($addEscortsToTotal) {
            $query2 = 'SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ? && bookingState = ?';
            $sumEscorts = $this->connection->fetchOne($query2, [$eventConfig->event->id, $bookingState]);

            if (false !== $sumEscorts) {
                $sumBookingTotal += $sumEscorts;
            }
        }

        return $sumBookingTotal;
    }

    /**
     * @return int|string
     */
    public function getBookingStartDate(EventConfig $eventConfig, string $format = 'timestamp')
    {
        $dateAdapter = $this->framework->getAdapter(Date::class);
        $configAdapter = $this->framework->getAdapter(Config::class);

        $tstamp = empty($eventConfig->event->bookingStartDate) ? 0 : (int) $eventConfig->event->bookingStartDate;

        if ('timestamp' === $format) {
            $varValue = $tstamp;
        } elseif ('date' === $format) {
            $varValue = (string) $dateAdapter->parse($configAdapter->get('dateFormat'), $tstamp);
        } elseif ('datim' === $format) {
            $varValue = (string) $dateAdapter->parse($configAdapter->get('datimFormat'), $tstamp);
        } else {
            $varValue = $tstamp;
        }

        return $varValue;
    }

    /**
     * @return int|string
     */
    public function getBookingEndDate(EventConfig $eventConfig, string $format = 'timestamp')
    {
        $dateAdapter = $this->framework->getAdapter(Date::class);
        $configAdapter = $this->framework->getAdapter(Config::class);

        $tstamp = empty($eventConfig->event->bookingEndDate) ? 0 : $eventConfig->event->bookingEndDate;

        if ('timestamp' === $format) {
            $varValue = (int) $tstamp;
        } elseif ('date' === $format) {
            $varValue = $dateAdapter->parse($configAdapter->get('dateFormat'), $tstamp);
        } elseif ('datim' === $format) {
            $varValue = $dateAdapter->parse($configAdapter->get('datimFormat'), $tstamp);
        } else {
            $varValue = (int) $tstamp;
        }

        return $varValue;
    }

    public function addToSession(EventConfig $eventConfig, CalendarEventsMemberModel $objEventMember, Form $objForm): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $flashBag = $session->getFlashBag();
        $arrSession = [];

        $arrSession['eventData'] = $eventConfig->event->row();
        $arrSession['memberData'] = $objEventMember->row();
        $arrSession['formData'] = $objForm->fetchAll();

        $flashBag->set(self::FLASH_KEY, $arrSession);
    }
}
