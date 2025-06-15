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

use Contao\CalendarEventsModel;
use Contao\FrontendUser;
use Contao\Template;

class AddTemplateData
{
    public function __construct(
        private readonly EventRegistration $eventRegistration,
    ) {
    }

    /**
     * Augment template with more event properties.
     */
    public function addTemplateData(Template $template, CalendarEventsModel $event): void
    {
        $template->canRegister = fn (): bool => $this->eventRegistration->canRegister($event);

        $template->isFullyBooked = fn (): bool => $this->eventRegistration->isFullyBooked($event);

        $template->bookingCount = fn (): int => $this->eventRegistration->getBookingCount($event);

        $template->bookingMin = fn (): int => $this->eventRegistration->getBookingMin($event);

        $template->bookingMax = fn (): int => $this->eventRegistration->getBookingMax($event);

        $template->bookingStartDate = fn (): string => $this->eventRegistration->getBookingStartDate($event, 'date');

        $template->bookingStartDatim = fn (): string => $this->eventRegistration->getBookingStartDate($event, 'datim');

        $template->bookingStartTimestamp = fn (): int => $this->eventRegistration->getBookingStartDate($event, 'timestamp');

        $template->bookingEndDate = fn (): string => $this->eventRegistration->getBookingEndDate($event, 'date');

        $template->bookingEndDatim = fn (): string => $this->eventRegistration->getBookingEndDate($event, 'datim');

        $template->bookingEndTimestamp = fn (): int => $this->eventRegistration->getBookingEndDate($event, 'timestamp');

        $template->hasLoggedInUser = fn (): bool => $this->eventRegistration->hasLoggedInFrontendUser();

        $template->loggedInUser = fn (): FrontendUser|null => $this->eventRegistration->getLoggedInFrontendUser();
    }
}
