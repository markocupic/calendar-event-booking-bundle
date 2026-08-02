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

namespace Markocupic\CalendarEventBookingBundle\EventListener\WaitingList;

use Contao\CalendarEventsModel;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\WaitingListPromotionProcessor;
use Markocupic\CalendarEventBookingBundle\Event\WaitingListPromotedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class WaitingListPromotedListener
{
    public function __construct(private WaitingListPromotionProcessor $waitingListPromotionProcessor)
    {
    }

    #[AsEventListener]
    public function onWaitingListPromoted(WaitingListPromotedEvent $event): void
    {
        if (!$event->isAdvancementAllowed()) {
            return;
        }

        $booking = $event->getBooking();

        /** @var CalendarEventsModel|null $calendarEvent */
        $calendarEvent = $booking->getRelated('pid');

        if (null === $calendarEvent) {
            return;
        }

        $this->waitingListPromotionProcessor->promoteBookingFromWaitingList($event->getBooking(), $calendarEvent->current(), $event->getContext());
    }
}
