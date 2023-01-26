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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PostBooking;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Monolog\ContaoContext;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Logger\Logger;
use Psr\Log\LogLevel;

#[AsHook(ContaoLog::HOOK, priority: 1100)]
final class ContaoLog
{
    public const HOOK = 'calEvtBookingPostBooking';

    public function __construct(
        private readonly ?Logger $logger = null,
    ) {
    }

    /**
     * @throws \Exception
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

        if (null !== $this->logger) {
            $this->logger->log($strText, $level, ContaoContext::GENERAL);
        }
    }
}
