<?php

declare(strict_types=1);

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Contao\Dca;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;

/**
 * Class TlModule
 * @package Markocupic\CalendarEventBookingBundle\Contao\Dca
 */
class TlModule
{

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * TlModule constructor.
     * @param ContaoFramework $framework
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @return array
     */
    public function getCalendarEventBookingMemberListTemplate(): array
    {
        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);
        return $controllerAdapter->getTemplateGroup('mod_calendar_event_booking_member_list');
    }

    /**
     * @return array
     */
    public function getCalendarEventBookingMemberListPartialTemplate(): array
    {
        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);
        return $controllerAdapter->getTemplateGroup('calendar_event_booking_member_list_partial');
    }

}
