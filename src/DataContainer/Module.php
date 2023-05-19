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
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;

class Module
{
    public const TABLE = 'tl_module';

    private Adapter $controller;

    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
        $this->controller = $this->framework->getAdapter(Controller::class);
    }

    #[AsCallback(table: self::TABLE, target: 'fields.cebb_memberListPartialTemplate.options')]
    public function getCalendarEventBookingMemberListPartialTemplate(): array
    {
        return $this->controller->getTemplateGroup('calendar_event_booking_member_list_partial');
    }
}
