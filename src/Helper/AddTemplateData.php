<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;

class AddTemplateData
{
    public function __construct(
        private readonly EventBooking    $eventBooking,
        private readonly ContaoFramework $framework,
    )    {
    }

    /**
     * Augment template with more properties.
     */
    public function addTemplateData(FragmentTemplate $template, CalendarEventsModel $event, Request $request): void
    {
        $this->framework->initialize();
        foreach ($this->getData($event, $request) as $key => $value) {
            $template->set($key, $value);
        }
    }

    public function getData(CalendarEventsModel $event, Request $request): array
    {
        return [
            'event'           => $event,
            'calendar'        => $event->getRelated('pid'),
            'canRegister'     => $this->eventBooking->canRegister($event, $request),
            'isFullyBooked'   => $this->eventBooking->isFullyBooked($event),
            'freeSpotsCount'  => $this->eventBooking->getFreeSpotsCount($event),
            'bookingCount'    => $this->eventBooking->getBookingCount($event),
            'hasLoggedInUser' => $this->eventBooking->hasLoggedInFrontendUser(),
            'loggedInUser'    => $this->eventBooking->getLoggedInFrontendUser(),
            'page'            => $request->attributes->get('pageModel'),
        ];
    }
}
