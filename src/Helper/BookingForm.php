<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2020 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\Input;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\Security\Core\Security;

class BookingForm
{
    /**
     * @var ContaoFramework
     */
    protected $framework;

    /**
     * @var Security
     */
    protected $security;

    /**
     * @var Security
     */
    protected $connection;

    public function __construct(ContaoFramework $framework, Connection $connection, Security $security)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->security = $security;
    }

    public function getEventFromUrl(): ?CalendarEventsModel
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

    /**
     * Return logged in frontend user, if there is one.
     */
    public function getLoggedInUser(): ?MemberModel
    {
        $user = $this->security->getUser();

        if ($user instanceof FrontendUser) {
            $memberModelAdapter = $this->framework->getAdapter(MemberModel::class);
            $objUser = $memberModelAdapter->findByPk($user->id);

            if (null !== $objUser) {
                return $objUser;
            }
        }

        return null;
    }

    public function loggedInUserIsAdmin(ModuleModel $module): bool
    {
        if (null !== ($objUser = $this->getLoggedInUser())) {
            $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
            $groupAdmins = $stringUtilAdapter->deserialize($module->calendar_event_booking_member_admin_member_groups, true);

            if (\in_array($objUser->id, $groupAdmins, false)) {
                return true;
            }
        }

        return false;
    }

    public function getCase(ModuleModel $module): string
    {
        $countBookings = $this->getNumberOfBookings();
        $objEvent = $this->getEventFromUrl();

        if (null === $objEvent) {
            $case = 'bookingNotYetPossible';
        } elseif ($this->loggedInUserIsAdmin($module)) {
            // User belongs to a frontend admin group, that is why the form will be displayed always
            $case = 'bookingPossible';
        } elseif ($objEvent->bookingStartDate > 0 && $objEvent->bookingStartDate > time()) {
            // User has to wait. Booking is not possible yet
            $case = 'bookingNotYetPossible';
        } elseif ($objEvent->bookingEndDate > 0 && $objEvent->bookingEndDate < time()) {
            // User is to late the sign in deadline has proceeded
            $case = 'bookingNoLongerPossible';
        } elseif ($countBookings > 0 && $objEvent->maxMembers > 0 && $countBookings >= $objEvent->maxMembers) {
            // Check if event is  fully booked
            $case = 'eventFullyBooked';
        } else {
            $case = 'bookingPossible';
        }

        return $case;
    }

    /**
     * @return int
     */
    public function getNumberOfBookings()
    {
        $objEvent = $this->getEventFromUrl();

        if (null !== $objEvent) {
            $calendarEventsMemberModelAdaper = $this->framework->getAdapter(CalendarEventsMemberModel::class);

            return (int) $calendarEventsMemberModelAdaper->countBy('pid', $this->objEvent->id);
        }

        return 0;
    }
}
