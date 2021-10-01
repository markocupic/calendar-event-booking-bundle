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

namespace Markocupic\CalendarEventBookingBundle\Event;

use Contao\CalendarEventsModel;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\EventDispatcher\GenericEvent;

class ValidateEventRegistrationRequestEvent extends AbstractEvent
{
    public const NAME = 'markocupic.calendar_event_booking.validate_event_booking_request';

    /**
     * @var GenericEvent
     */
    protected $event;

    public function __construct(GenericEvent $event)
    {
        $this->event = $event;
    }


}