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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class AddTemplateData
{
    public function __construct(
        private readonly FrontendUserManager $frontendUserManager,
        private readonly EventStatus $eventStatus,
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
    ) {
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
            'event' => $event->current(),
            'calendar' => $event->getRelated('pid')?->current(),
            'canRegister' => $this->eventStatus->canRegister($event, $request),
            'isFullyBooked' => $this->eventStatus->isFullyBooked($event, $this->connection),
            'freeSpotsCount' => $this->eventStatus->getFreeSpotsCount($event, $this->connection),
            'bookingCount' => $this->eventStatus->getBookingCount($event, $this->connection),
            'hasLoggedInUser' => $this->frontendUserManager->hasLoggedInFrontendUser(),
            'loggedInUser' => $this->frontendUserManager->getLoggedInFrontendUser(),
            'page' => $request->attributes->get('pageModel')->current(),
        ];
    }
}
