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
use Contao\Input;

class EventUrlResolver
{
    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    public function resolve(): CalendarEventsModel|null
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        $this->handleAutoItemParameter($inputAdapter);

        $eventIdOrAlias = $inputAdapter->get('events');

        if (empty($eventIdOrAlias)) {
            return null;
        }

        return $calendarEventsModelAdapter->findByIdOrAlias($eventIdOrAlias);
    }

    private function handleAutoItemParameter($inputAdapter): void
    {
        $hasNoEventsParameter = empty($inputAdapter->get('events'));
        $hasAutoItemParameter = isset($_GET['auto_item']);

        if ($hasNoEventsParameter && $hasAutoItemParameter) {
            $inputAdapter->setGet('events', Input::get('auto_item'));
        }
    }
}
