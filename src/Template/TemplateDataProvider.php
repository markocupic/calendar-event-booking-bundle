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

namespace Markocupic\CalendarEventBookingBundle\Template;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\BookingCapacity;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\EventStatusResolver;
use Markocupic\CalendarEventBookingBundle\Security\User\FrontendUserAccessor;
use Symfony\Component\HttpFoundation\Request;

class TemplateDataProvider
{
    public function __construct(
        private readonly FrontendUserAccessor $frontendUserAccessor,
        private readonly BookingCapacity $bookingCapacity,
        private readonly EventStatusResolver $eventStatusResolver,
    ) {
    }

    /**
     * Enrich the template with more properties.
     */
    public function addData(FragmentTemplate $template, CalendarEventsModel $event, Request $request): void
    {
        foreach ($this->getData($event, $request) as $key => $value) {
            $template->set($key, $value);
        }
    }

    public function getData(CalendarEventsModel $event, Request $request): array
    {
        return [
            'event' => $event->current(),
            'calendar' => $event->getRelated('pid')?->current(),
            'canRegister' => $this->eventStatusResolver->canRegister($event, $request),
            'isFullyBooked' => $this->bookingCapacity->isFullyBooked($event),
            'freeSpotsCount' => $this->bookingCapacity->getFreeSpotsCount($event),
            'bookingCount' => $this->bookingCapacity->getBookingCount($event),
            'hasLoggedInUser' => $this->frontendUserAccessor->hasLoggedInFrontendUser(),
            'loggedInUser' => $this->frontendUserAccessor->getLoggedInFrontendUser(),
            'page' => $request->attributes->get('pageModel'),
        ];
    }
}
