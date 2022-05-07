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

namespace Markocupic\CalendarEventBookingBundle\Config;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;

class EventConfig
{
    private CalendarEventsModel $event;
    private ContaoFramework $framework;

    // Adapters
    private Adapter $config;

    public function __construct(CalendarEventsModel $event, ContaoFramework $framework)
    {
        $this->event = $event;
        $this->framework = $framework;

        // Adapters
        $this->config = $this->framework->getAdapter(Config::class);
    }

    public function get($propertyName)
    {
        // @todo enable presetting values in tl_calendar
        return $this->getEvent()->$propertyName;
    }

    public function getEvent(): CalendarEventsModel
    {
        return $this->event;
    }

    public function isBookingFormActivated(): bool
    {
        return (bool) $this->get('activateBookingForm');
    }

    public function isWaitingListActivated(): bool
    {
        return (bool) $this->get('activateWaitingList');
    }

    public function isNotificationActivated(): bool
    {
        return (bool) $this->get('activateNotification');
    }

    public function getBookingStartDate(): string
    {
        return date($this->config->dateFormat, (int) $this->bookingStartDate);
    }

    public function getBookingEndDate(): string
    {
        return date($this->config->dateFormat, (int) $this->bookingEndDate);
    }

    public function getBookingMax(): int
    {
        return (int) $this->get('maxMembers');
    }

    public function getBookingMin(): int
    {
        return (int) $this->get('minMembers');
    }
}
