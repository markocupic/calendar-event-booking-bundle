<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\PostBooking;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventBooking\Logger\Logger;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Psr\Log\LogLevel;

/**
 * @Hook(ContaoLog::HOOK, priority=ContaoLog::PRIORITY)
 */
final class ContaoLog extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_POST_BOOKING;
    public const PRIORITY = 1100;

    private Connection $connection;
    private ?Logger $logger;

    public function __construct(Connection $connection, ?Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function __invoke(EventConfig $eventConfig, EventRegistration $eventRegistration): void
    {
        if (!self::isEnabled()) {
            return;
        }

        if (false === $this->connection->fetchOne('SELECT id FROM tl_calendar_events_member WHERE id = ?', [$eventRegistration->getModel()->id])) {
            return;
        }

        $strText = sprintf(
            'New event subscription with ID %d for event with ID %d (%s).',
            $eventRegistration->getModel()->id,
            $eventConfig->getModel()->id,
            $eventConfig->getModel()->title
        );

        $level = LogLevel::INFO;
        $this->logger->log($strText, $level, ContaoContext::GENERAL);
    }
}
