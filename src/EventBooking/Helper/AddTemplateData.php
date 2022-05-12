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

use Contao\FrontendUser;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;
use Symfony\Component\Security\Core\Security;

final class AddTemplateData
{
    private EventRegistration $eventRegistration;
    private Security $security;
    private BookingValidator $bookingValidator;

    public function __construct(EventRegistration $evenRegistration, Security $security, BookingValidator $bookingValidator)
    {
        $this->eventRegistration = $evenRegistration;
        $this->security = $security;
        $this->bookingValidator = $bookingValidator;
    }

    /**
     * Augment template with more event properties.
     */
    public function addTemplateData(EventConfig $eventConfig, Template $template): void
    {
        $template->canRegister = fn (): bool => $this->bookingValidator->validateCanRegister($eventConfig);

        $template->isFullyBooked = fn (): bool => $this->eventRegistration->isFullyBooked($eventConfig);

        $template->confirmedBookingsCount = fn (): int => $this->eventRegistration->getConfirmedBookingsCount($eventConfig);

        $template->bookingMin = static fn (): int => $eventConfig->getBookingMin();

        $template->bookingMax = static fn (): int => $eventConfig->getBookingMax($eventConfig);

        $template->bookingStartDate = static fn (): string => $eventConfig->getBookingStartDate('date');

        $template->bookingStartDatim = static fn (): string => $eventConfig->getBookingStartDate('datim');

        $template->bookingStartTimestamp = static fn (): string => $eventConfig->getBookingStartDate('timestamp');

        $template->bookingEndDate = static fn (): string => $eventConfig->getBookingEndDate('date');

        $template->bookingEndDatim = static fn (): string => $eventConfig->getBookingEndDate('datim');

        $template->bookingEndTimestamp = static fn (): string => $eventConfig->getBookingEndDate('timestamp');

        $template->hasLoggedInUser = fn (): bool => $this->hasLoggedInFrontendUser();

        $template->loggedInUser = fn (): ?FrontendUser => $this->getLoggedInFrontendUser();

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
