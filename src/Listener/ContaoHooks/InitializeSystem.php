<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class InitializeSystem
 * @package Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks
 */
class InitializeSystem
{

    /** @var RequestStack  */
    private $requestStack;

    /** @var ScopeMatcher  */
    private $scopeMatcher;

    /**
     * InitializeSystem constructor.
     * @param RequestStack $requestStack
     * @param ScopeMatcher $scopeMatcher
     */
    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher)
    {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Register hooks && enable hook overriding in a custom module
     * $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'] is set in config.php
     */
    public function registerCalendarEventBookingHooks()
    {
        // If is frontend mode
        if (!$this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest()))
        {
            // Register hook && enable hook overriding in a custom module
            // $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'] is set in config.php
            if (!empty($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']) && is_array($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']))
            {
                foreach ($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'] as $key => $arrHook)
                {
                    if (!empty($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'][$key]) && is_array($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'][$key]))
                    {
                        if (count($arrHook) === 2)
                        {
                            $GLOBALS['TL_HOOKS'][$key][] = $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'][$key];
                        }
                    }
                }
            }
        }
    }

}
