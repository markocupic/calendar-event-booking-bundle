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
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;

class AddTemplateData
{
    public function __construct(
        private readonly EventBooking $eventBooking,
        private readonly ContaoFramework $framework,
    ) {
    }

    /**
     * Augment template with more properties.
     */
    public function addTemplateData(Template $template, CalendarEventsModel $event, Request $request): void
    {
        $this->framework->initialize();

        $template->event = $event;

        $template->calendar = $event->getRelated('pid');

        $template->canRegister = $this->eventBooking->canRegister($event, $request);

        $template->isFullyBooked = $this->eventBooking->isFullyBooked($event);

        $template->freeSpotsCount = $this->eventBooking->getFreeSpotsCount($event);

        $template->bookingCount = $this->eventBooking->getBookingCount($event);

        $template->hasLoggedInUser = $this->eventBooking->hasLoggedInFrontendUser();

        $template->loggedInUser = $this->eventBooking->getLoggedInFrontendUser();

        $template->page = $request->attributes->get('pageModel');
    }
}
