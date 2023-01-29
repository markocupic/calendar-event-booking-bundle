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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\PostBooking;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Monolog\ContaoContext;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventBooking\Logger\Logger;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Psr\Log\LogLevel;

#[AsHook(ContaoLog::HOOK)]
final class ContaoLog extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_POST_BOOKING;

    public function __construct(
        private readonly Connection $connection,
        private readonly Logger|null $logger = null,
    ) {
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
        $this->logger?->log($strText, $level, ContaoContext::GENERAL);
    }
}
