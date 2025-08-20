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

class SendNotificationEvent extends Event
{
    private bool $shouldSend = true;

    public function __construct(
        private readonly int $notificationId,
        private array $tokens,
        private readonly CalendarEventsMemberModel $booking,
        private readonly Request $request,
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getNotificationId(): int
    {
        return $this->notificationId;
    }

    public function getBooking(): CalendarEventsMemberModel
    {
        return $this->booking;
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function setTokens(array $tokens): void
    {
        $this->tokens = $tokens;
    }

    public function shouldSend(): bool
    {
        return $this->shouldSend;
    }

    public function setShouldSend(bool $shouldSend): void
    {
        $this->shouldSend = $shouldSend;
    }
}
