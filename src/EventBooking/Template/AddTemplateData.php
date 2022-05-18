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

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Template;

use Contao\FrontendUser;
use Contao\Template;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;
use Symfony\Component\Security\Core\Security;

final class AddTemplateData
{
    private Security $security;
    private BookingValidator $bookingValidator;

    public function __construct(Security $security, BookingValidator $bookingValidator)
    {
        $this->security = $security;
        $this->bookingValidator = $bookingValidator;
    }

    /**
     * Augment template with more event properties.
     *
     * @throws Exception
     */
    public function addTemplateData(EventConfig $eventConfig, Template $template): void
    {
        $template->canRegister = $this->bookingValidator->validateCanRegister($eventConfig);

        $template->isFullyBooked = $eventConfig->isFullyBooked();

        $template->numberFreeSeats = $eventConfig->getNumberOfFreeSeats();

        $template->numberFreeSeatsWaitingList = $eventConfig->getNumberOfFreeSeats(true);

        $template->confirmedBookingsCount = $eventConfig->getConfirmedBookingsCount();

        $template->bookingMin = $eventConfig->getBookingMin();

        $template->bookingMax = $eventConfig->getBookingMax();

        $template->bookingStartDate = $eventConfig->getBookingStartDate('date');

        $template->bookingStartDatim = $eventConfig->getBookingStartDate('datim');

        $template->bookingStartTimestamp = $eventConfig->getBookingStartDate('timestamp');

        $template->getBookingEndDate = $eventConfig->getBookingEndDate('date');

        $template->getBookingEndDatim = $eventConfig->getBookingEndDate('datim');

        $template->getBookingEndTimestamp = $eventConfig->getBookingEndDate('timestamp');

        $template->hasLoggedInUser = $this->hasLoggedInFrontendUser();

        $template->getLoggedInUser = $this->getLoggedInFrontendUser();

        $template->event = $eventConfig->getModel();

        $template->eventConfig = $eventConfig;
    }

    private function hasLoggedInFrontendUser(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof FrontendUser;
    }

    private function getLoggedInFrontendUser(): ?FrontendUser
    {
        $user = $this->security->getUser();

        if ($user instanceof FrontendUser) {
            return $user;
        }

        return null;
    }
}
