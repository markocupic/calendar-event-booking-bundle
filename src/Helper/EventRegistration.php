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

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class EventRegistration
{
    public const FLASH_KEY = '_event_registration';

    /**
     * @var ContaoFramework
     */
    protected $framework;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var RequestStack
     */
    private $requestStack;

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

    public function getEventFromCurrentUrl(): ?CalendarEventsModel
    {
        $configAdapter = $this->framework->getAdapter(Config::class);
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        // Set the item from the auto_item parameter
        if (!isset($_GET['events']) && $configAdapter->get('useAutoItem') && isset($_GET['auto_item'])) {
            $inputAdapter->setGet('events', $inputAdapter->get('auto_item'));
        }

        // Return an empty string if "events" is not set
        if ('' !== $inputAdapter->get('events')) {
            if (null !== ($objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events')))) {
                return $objEvent;
            }
        }

        return null;
    }

    public function getRegistrationState(?CalendarEventsModel $objEvent): string
    {
        if (!$objEvent->addBookingForm) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_FORM_DISABLED;
        } elseif ($objEvent->bookingStartDate > time()) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NOT_YET_POSSIBLE;
        } elseif (is_numeric($objEvent->bookingEndDate) && $objEvent->bookingEndDate < time()) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NO_LONGER_POSSIBLE;
        } elseif ($this->isFullyBooked($objEvent)) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_EVENT_FULLY_BOOKED;
        } else {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_POSSIBLE;
        }

        return $state;
    }

    public function canRegister(CalendarEventsModel $objEvent): bool
    {
        return CalendarEventBookingEventBookingModuleController::CASE_BOOKING_POSSIBLE === $this->getRegistrationState($objEvent);
    }

    /**
     * @throws Exception
     */
    public function isFullyBooked(CalendarEventsModel $objEvent): bool
    {
        $bookingCount = $this->getBookingCount($objEvent);
        $bookingMax = $this->getBookingMax($objEvent);

        if ($bookingMax > 0 && $bookingCount >= $bookingMax) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function getBookingCount(CalendarEventsModel $objEvent): int
    {
        $calendarEventsMemberModelAdaper = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $memberCount = (int) $calendarEventsMemberModelAdaper->countBy('pid', $objEvent->id);

        if ($objEvent->includeEscortsWhenCalculatingRegCount) {
            $query = 'SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ?';
            $sum = $this->connection->fetchOne($query, [$objEvent->id]);

            if (false !== $sum) {
                $memberCount = $sum + $memberCount;
            }
        }

        return $memberCount;
    }

    public function getBookingMax(CalendarEventsModel $objEvent): int
    {
        return (int) $objEvent->maxMembers;
    }

    public function getBookingMin(CalendarEventsModel $objEvent): int
    {
        return (int) $objEvent->minMembers;
    }

    /**
     * @return int|string
     */
    public function getBookingStartDate(CalendarEventsModel $objEvent, string $format = 'timestamp')
    {
        $dateAdapter = $this->framework->getAdapter(Date::class);
        $configAdapter = $this->framework->getAdapter(Config::class);

        $tstamp = empty($objEvent->bookingStartDate) ? 0 : (int) $objEvent->bookingStartDate;

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
    public function getBookingEndDate(CalendarEventsModel $objEvent, string $format = 'timestamp')
    {
        $dateAdapter = $this->framework->getAdapter(Date::class);
        $configAdapter = $this->framework->getAdapter(Config::class);

        $tstamp = empty($objEvent->bookingEndDate) ? 0 : $objEvent->bookingEndDate;

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

    public function addToSession(CalendarEventsModel $objEvent, CalendarEventsMemberModel $objEventMember, Form $objForm): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $flashBag = $session->getFlashBag();
        $arrSession = [];

        $arrSession['eventData'] = $objEvent->row();
        $arrSession['memberData'] = $objEventMember->row();
        $arrSession['formData'] = $objForm->fetchAll();

        $flashBag->set(self::FLASH_KEY, $arrSession);
    }
}
