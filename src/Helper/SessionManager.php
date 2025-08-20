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

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionManager
{
    public const FLASH_KEY = '_event_booking';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function addToSession(CalendarEventsModel $event, CalendarEventsMemberModel $booking, array $formData): void
    {
        $session = $this->getSession();

        $flashBag = $session->getFlashBag();
        $flashBag->set('_event_booking', [
            'eventData' => $event->row(),
            'memberData' => $booking->row(),
            'formData' => $formData,
        ]);
    }

    private function getSession(): SessionInterface
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        return $session;
    }
}
