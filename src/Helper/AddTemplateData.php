<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
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
    /**
     * @var EventRegistration
     */
    private $eventRegistration;

    public function __construct(EventRegistration $evenRegistration)
    {
        $this->eventRegistration = $evenRegistration;
    }

    /**
     * Augment template with more event properties.
     */
    public function addTemplateData(Template $template, CalendarEventsModel $objEvent): void
    {
        $template->canRegister = function () use ($objEvent): bool {
            return $this->eventRegistration->canRegister($objEvent);
        };

        $template->isFullyBooked = function () use ($objEvent): bool {
            return $this->eventRegistration->isFullyBooked($objEvent);
        };

        $template->bookingCount = function () use ($objEvent): int {
            return $this->eventRegistration->getBookingCount($objEvent);
        };

        $template->bookingMin = function () use ($objEvent): int {
            return $this->eventRegistration->getBookingMin($objEvent);
        };

        $template->bookingMax = function () use ($objEvent): int {
            return $this->eventRegistration->getBookingMax($objEvent);
        };

        $template->bookingStartDate = function () use ($objEvent): int {
            return $this->eventRegistration->getBookingStartDate($objEvent);
        };

        $template->bookingEndDate = function () use ($objEvent): int {
            return $this->eventRegistration->getBookingEndDate($objEvent);
        };

        $template->hasLoggedInFrontendUser = function (): bool {
            return $this->eventRegistration->hasLoggedInFrontendUser();
        };

        $template->loggedInFrontendUser = function (): ?FrontendUser {
            return $this->eventRegistration->getLoggedInFrontendUser();
        };
    }
}
