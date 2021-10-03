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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PostBooking;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Logger\Logger;
use Psr\Log\LogLevel;

/**
 * @Hook(ContaoLog::HOOK, priority=ContaoLog::PRIORITY)
 */
final class ContaoLog
{
    public const HOOK = 'calEvtBookingPostBooking';
    public const PRIORITY = 1100;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Contao system log.
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance, array $arrDisabledHooks = []): void
    {
        if (\in_array(self::class, $arrDisabledHooks, true)) {
            return;
        }

        /** @var CalendarEventsModel $objEvent */
        $objEvent = $moduleInstance->getProperty('objEvent');

        $strText = 'New booking for event with title "'.$objEvent->title.'"';
        $level = LogLevel::INFO;
        $this->logger->log($strText, $level);
    }
}
