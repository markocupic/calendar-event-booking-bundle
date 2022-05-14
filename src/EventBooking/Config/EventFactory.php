<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Config;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use http\Exception\InvalidArgumentException;

class EventFactory
{
    private ContaoFramework $framework;

    // Adapters
    private Adapter $eventModel;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;

        // Adapters
        $this->eventModel = $this->framework->getAdapter(CalendarEventsModel::class);
    }

    public function create(?CalendarEventsModel $event): EventConfig
    {
        if (null === $event) {
            throw new InvalidArgumentException('Event not found!');
        }

        return new EventConfig($event, $this->framework);
    }
}