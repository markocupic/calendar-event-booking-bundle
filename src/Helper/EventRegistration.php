<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Codefog\HasteBundle\Form\Form;
use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\RequestStack;

class EventRegistration
{
    public const FLASH_KEY = '_event_registration';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly TokenChecker $tokenChecker,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function hasLoggedInFrontendUser(): bool
    {
        return $this->tokenChecker->hasFrontendUser();
    }

    public function getLoggedInFrontendUser(): FrontendUser|null
    {
        if (!$this->tokenChecker->hasFrontendUser()) {
            return null;
        }

        return FrontendUser::getInstance();
    }

    public function getEventFromCurrentUrl(): CalendarEventsModel|null
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        // Set the item from the auto_item parameter
        if (empty($inputAdapter->get('events')) && isset($_GET['auto_item'])) {
            $inputAdapter->setGet('events', Input::get('auto_item'));
        }

        $eventIdOrAlias = $inputAdapter->get('events');

        // Return an empty string if "events" is not set
        if (!empty($eventIdOrAlias)) {
            if (null !== ($event = $calendarEventsModelAdapter->findByIdOrAlias($eventIdOrAlias))) {
                return $event;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getRegistrationState(CalendarEventsModel|null $event): string
    {
        if (!$event->addBookingForm) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_FORM_DISABLED;
        } elseif ($event->bookingStartDate > time()) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NOT_YET_POSSIBLE;
        } elseif (is_numeric($event->bookingEndDate) && $event->bookingEndDate < time()) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_NO_LONGER_POSSIBLE;
        } elseif ($this->isFullyBooked($event)) {
            $state = CalendarEventBookingEventBookingModuleController::CASE_EVENT_FULLY_BOOKED;
        } else {
            $state = CalendarEventBookingEventBookingModuleController::CASE_BOOKING_POSSIBLE;
        }

        return $state;
    }

    /**
     * @throws Exception
     */
    public function canRegister(CalendarEventsModel $event): bool
    {
        return CalendarEventBookingEventBookingModuleController::CASE_BOOKING_POSSIBLE === $this->getRegistrationState($event);
    }

    /**
     * @throws Exception
     */
    public function isFullyBooked(CalendarEventsModel $event): bool
    {
        $bookingCount = $this->getBookingCount($event);
        $bookingMax = $this->getBookingMax($event);

        if ($bookingMax > 0 && $bookingCount >= $bookingMax) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function getBookingCount(CalendarEventsModel $event): int
    {
        $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $memberCount = (int) $calendarEventsMemberModelAdapter->countBy('pid', $event->id);

        if ($event->includeEscortsWhenCalculatingRegCount) {
            $query = 'SELECT SUM(escorts) FROM tl_calendar_events_member WHERE pid = ?';
            $sum = $this->connection->fetchOne($query, [$event->id]);

            if (false !== $sum) {
                $memberCount = $sum + $memberCount;
            }
        }

        return $memberCount;
    }

    public function getBookingMax(CalendarEventsModel $event): int
    {
        return (int) $event->maxMembers;
    }

    public function getBookingMin(CalendarEventsModel $event): int
    {
        return (int) $event->minMembers;
    }

    public function getBookingStartDate(CalendarEventsModel $event, string $format = 'timestamp'): int|string
    {
        $dateAdapter = $this->framework->getAdapter(Date::class);
        $configAdapter = $this->framework->getAdapter(Config::class);

        $tstamp = empty($event->bookingStartDate) ? 0 : (int) $event->bookingStartDate;

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

    public function getBookingEndDate(CalendarEventsModel $event, string $format = 'timestamp'): int|string
    {
        $dateAdapter = $this->framework->getAdapter(Date::class);
        $configAdapter = $this->framework->getAdapter(Config::class);

        $tstamp = empty($event->bookingEndDate) ? 0 : $event->bookingEndDate;

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

    public function addToSession(CalendarEventsModel $event, CalendarEventsMemberModel $registration, Form $form): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $flashBag = $session->getFlashBag();
        $arrSession = [];

        $arrSession['eventData'] = $event->row();
        $arrSession['memberData'] = $registration->row();
        $arrSession['formData'] = $form->fetchAll();

        $flashBag->set(self::FLASH_KEY, $arrSession);
    }
}
