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
use Contao\FormModel;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\Event;

class PostBookingEvent extends Event
{
    public const NAME = 'markocupic.calendar_event_booking.post_booking';

    /**
     * @var GenericEvent
     */
    protected $event;

    public function __construct(GenericEvent $event)
    {
        $this->event = $event;
    }

    public function getEvent(): CalendarEventsModel
    {
        return $this->event->getArgument('objEvent');
    }

    public function getEventMember(): CalendarEventsMemberModel
    {
        return $this->event->getArgument('objEventMember');
    }

    public function getForm(): Form
    {
        return $this->event->getArgument('objForm');
    }

    public function getFormModel(): FormModel
    {
        return $this->event->getArgument('objFormModel');
    }
}
