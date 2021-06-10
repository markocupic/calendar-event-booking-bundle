<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @Hook(InitializeSystem::HOOK)
 */
class InitializeSystem
{
    public const HOOK = 'initializeSystem';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * InitializeSystem constructor.
     */
    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher)
    {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Register hooks && enable hook overriding in a custom module
     * $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'] is set in config.php.
     */
    public function __invoke(): void
    {
        // If is frontend mode
        if (null !== $this->requestStack->getCurrentRequest()) {
            if (!$this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest())) {
                // Register hook && enable hook overriding in a custom module
                // $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'] is set in config.php
                if (!empty($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']) && \is_array($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'])) {
                    foreach ($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'] as $key => $arrHook) {
                        if (!empty($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'][$key]) && \is_array($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'][$key])) {
                            if (2 === \count($arrHook)) {
                                $GLOBALS['TL_HOOKS'][$key][] = $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'][$key];
                            }
                        }
                    }
                }
            }
        }
    }
}
