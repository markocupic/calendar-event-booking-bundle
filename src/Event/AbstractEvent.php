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

use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractEvent extends Event
{
    protected $disabledSubscribers = [];

    public function getBookingModuleInstance(): ?CalendarEventBookingEventBookingModuleController
    {
        /** @var GenericEvent $event */
        $event = $this->event;

        /** @var CalendarEventBookingEventBookingModuleController $moduleInstance */
        return $event->getArgument('moduleInstance');
    }

    public function disableSubscriber(string $strSubscriber): void
    {
        $this->disabledSubscribers[] = $strSubscriber;
        $this->disabledSubscribers = array_unique($this->disabledSubscribers);
    }

    public function isDisabled($strSubscriber): bool
    {
        if (\in_array($strSubscriber, $this->disabledSubscribers, true)) {
            return true;
        }

        return false;
    }

    public function getDisabledSubscribers(): array
    {
        return $this->disabledSubscribers;
    }
}
