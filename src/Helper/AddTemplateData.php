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

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Contao\FrontendUser;
use Contao\Template;

class AddTemplateData
{
    private EventRegistration $eventRegistration;

    public function __construct(EventRegistration $evenRegistration)
    {
        $this->eventRegistration = $evenRegistration;
    }

    /**
     * Augment template with more event properties.
     */
    public function addTemplateData(Template $template, CalendarEventsModel $objEvent): void
    {
        $template->canRegister = fn (): bool => $this->eventRegistration->canRegister($objEvent);

        $template->isFullyBooked = fn (): bool => $this->eventRegistration->isFullyBooked($objEvent);

        $template->bookingCount = fn (): int => $this->eventRegistration->getBookingCount($objEvent);

        $template->bookingMin = fn (): int => $this->eventRegistration->getBookingMin($objEvent);

        $template->bookingMax = fn (): int => $this->eventRegistration->getBookingMax($objEvent);

        $template->bookingStartDate = fn (): string => $this->eventRegistration->getBookingStartDate($objEvent, 'date');

        $template->bookingStartDatim = fn (): string => $this->eventRegistration->getBookingStartDate($objEvent, 'datim');

        $template->bookingStartTimestamp = fn (): int => $this->eventRegistration->getBookingStartDate($objEvent, 'timestamp');

        $template->bookingEndDate = fn (): string => $this->eventRegistration->getBookingEndDate($objEvent, 'date');

        $template->bookingEndDatim = fn (): string => $this->eventRegistration->getBookingEndDate($objEvent, 'datim');

        $template->bookingEndTimestamp = fn (): int => $this->eventRegistration->getBookingEndDate($objEvent, 'timestamp');

        $template->hasLoggedInUser = fn (): bool => $this->eventRegistration->hasLoggedInFrontendUser();

        $template->loggedInUser = fn (): ?FrontendUser => $this->eventRegistration->getLoggedInFrontendUser();
    }
}
