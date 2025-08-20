<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Event;

use Contao\CalendarEventsModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class ResolveEventStatusEvent extends Event
{
    public function __construct(
        private readonly CalendarEventsModel $calendarEvent,
        private readonly Request $request,
        private string $eventStatus,
    ) {
    }

    public function getRequest(): Request|null
    {
        return $this->request;
    }

    public function getEventStatus(): string
    {
        return $this->eventStatus;
    }

    public function getCalendarEvent(): CalendarEventsModel
    {
        return $this->calendarEvent;
    }

    public function setEventStatus(string $eventStatus): void
    {
        $this->eventStatus = $eventStatus;
    }
}
