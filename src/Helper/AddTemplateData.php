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
use Contao\FrontendUser;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Config\EventFactory;

class AddTemplateData
{
    private EventRegistration $eventRegistration;
    private EventFactory $eventFactory;

    public function __construct(EventRegistration $evenRegistration, EventFactory $eventFactory)
    {
        $this->eventRegistration = $evenRegistration;
        $this->eventFactory = $eventFactory;
    }

    /**
     * Augment template with more event properties.
     */
    public function addTemplateData(Template $template, CalendarEventsModel $objEvent): void
    {
        $eventConfig = $this->eventFactory->create($objEvent->id);

        $template->canRegister = fn (): bool => $this->eventRegistration->canRegister($eventConfig);

        $template->isFullyBooked = fn (): bool => $this->eventRegistration->isFullyBooked($eventConfig);

        $template->bookingCount = fn (): int => $this->eventRegistration->getBookingCount($eventConfig);

        $template->bookingMin = static fn (): int => $eventConfig->getBookingMin();

        $template->bookingMax = static fn (): int => $eventConfig->getBookingMax($eventConfig);

        $template->bookingStartDate = fn (): string => $this->eventRegistration->getBookingStartDate($eventConfig, 'date');

        $template->bookingStartDatim = fn (): string => $this->eventRegistration->getBookingStartDate($eventConfig, 'datim');

        $template->bookingStartTimestamp = fn (): int => $this->eventRegistration->getBookingStartDate($eventConfig, 'timestamp');

        $template->bookingEndDate = fn (): string => $this->eventRegistration->getBookingEndDate($eventConfig, 'date');

        $template->bookingEndDatim = fn (): string => $this->eventRegistration->getBookingEndDate($eventConfig, 'datim');

        $template->bookingEndTimestamp = fn (): int => $this->eventRegistration->getBookingEndDate($eventConfig, 'timestamp');

        $template->hasLoggedInUser = fn (): bool => $this->eventRegistration->hasLoggedInFrontendUser();

        $template->loggedInUser = fn (): ?FrontendUser => $this->eventRegistration->getLoggedInFrontendUser();
    }
}
