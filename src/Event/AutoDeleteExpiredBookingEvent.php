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

use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class AutoDeleteExpiredBookingEvent extends Event
{
    private bool $shouldDelete = true;

    public function __construct(
        private readonly CalendarEventsMemberModel $booking,
        private readonly string $context,
        private readonly Request|null $request,
    ) {
    }

    public function getRequest(): Request|null
    {
        return $this->request;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getBooking(): CalendarEventsMemberModel
    {
        return $this->booking;
    }

    public function shouldDelete(): bool
    {
        return $this->shouldDelete;
    }

    public function setShouldDelete(bool $shouldDelete): bool
    {
        return $this->shouldDelete = $shouldDelete;
    }
}
