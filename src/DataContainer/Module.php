<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;

class Module
{
    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    public function getCalendarEventBookingMemberListPartialTemplate(): array
    {
        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        return $controllerAdapter->getTemplateGroup('calendar_event_booking_member_list_partial');
    }
}
