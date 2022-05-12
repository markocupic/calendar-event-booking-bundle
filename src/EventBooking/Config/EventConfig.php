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
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;

class EventConfig
{
    private CalendarEventsModel $event;
    private ContaoFramework $framework;

    // Adapters
    private Adapter $config;
    private Adapter $date;

    public function __construct(CalendarEventsModel $event, ContaoFramework $framework)
    {
        $this->event = $event;
        $this->framework = $framework;

        // Adapters
        $this->config = $this->framework->getAdapter(Config::class);
        $this->date = $this->framework->getAdapter(Date::class);
    }

    public function get($propertyName)
    {
        // @todo enable presetting values in tl_calendar
        return $this->getModel()->$propertyName;
    }

    public function getModel(): CalendarEventsModel
    {
        return $this->event;
    }

    public function isBookable(): bool
    {
        return (bool) $this->get('activateBookingForm');
    }

    public function hasWaitingList(): bool
    {
        return (bool) $this->get('activateWaitingList');
    }

    public function getWaitingListLimit(): int
    {
        return (int) $this->event->waitingListLimit;
    }

    public function isNotificationActivated(): bool
    {
        return (bool) $this->get('activateBookingNotification');
    }

    /**
     * @return string
     */
    public function getBookingStartDate(string $format = 'timestamp')
    {
        $tstamp = empty($this->event->bookingStartDate) ? 0 : (int) $this->event->bookingStartDate;

        if ('timestamp' === $format) {
            $varValue = (string) $tstamp;
        } elseif ('date' === $format) {
            $varValue = $this->date->parse($this->config->get('dateFormat'), $tstamp);
        } elseif ('datim' === $format) {
            $varValue = $this->date->parse($this->config->get('datimFormat'), $tstamp);
        } else {
            $varValue = (string) $tstamp;
        }

        return $varValue;
    }

    public function getBookingEndDate(string $format = 'timestamp'): string
    {
        $tstamp = empty($this->event->bookingEndDate) ? 0 : (int) $this->event->bookingEndDate;

        if ('timestamp' === $format) {
            $varValue = (string) $tstamp;
        } elseif ('date' === $format) {
            $varValue = $this->date->parse($this->config->get('dateFormat'), $tstamp);
        } elseif ('datim' === $format) {
            $varValue = $this->date->parse($this->config->get('datimFormat'), $tstamp);
        } else {
            $varValue = (string) $tstamp;
        }

        return $varValue;
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
