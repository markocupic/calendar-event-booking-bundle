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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PostBooking;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Logger\Logger;
use Psr\Log\LogLevel;

/**
 * @Hook(ContaoLog::HOOK, priority=ContaoLog::PRIORITY)
 */
final class ContaoLog extends AbstractHook
{
    public const HOOK = 'calEvtBookingPostBooking';
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
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance, int $insertId): void
    {
        if (!self::isEnabled()) {
            return;
        }

        if (false === $this->connection->fetchOne('SELECT id FROM tl_calendar_events_member WHERE id = ?', [$insertId])) {
            return;
        }

        /** @var EventConfig $eventConfig */
        $eventConfig = $moduleInstance->getProperty('eventConfig');

        $strText = sprintf(
            'New event subscription with ID %d for event with ID %d (%s).',
            $insertId,
            $eventConfig->event->id,
            $eventConfig->event->title
        );

        $level = LogLevel::INFO;
        $this->logger->log($strText, $level, ContaoContext::GENERAL);
    }
}
