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

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventBooking\Notification\Notification;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;

/**
 * @Hook(SendNotification::HOOK, priority=SendNotification::PRIORITY)
 */
final class SendNotification extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_POST_BOOKING;
    public const PRIORITY = 1000;

    private ContaoFramework $framework;
    private Connection $connection;
    private Notification $notification;

    // Adapters
    private Adapter $stringUtil;

    public function __construct(ContaoFramework $framework, Connection $connection, Notification $notification)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->notification = $notification;

        // Adapters
        $this->stringUtil = $this->framework->getAdapter(StringUtil::class);
    }

    /**
     * Run post booking notification.
     *
     * @throws \Exception
     */
    public function __invoke(EventConfig $eventConfig, EventRegistration $eventRegistration): void
    {
        if (!self::isEnabled()) {
            return;
        }

        if (!$eventConfig->get('activateBookingNotification')) {
            return;
        }

        if (false === $this->connection->fetchOne('SELECT id FROM tl_calendar_events_member WHERE id = ?', [$eventRegistration->getModel()->id])) {
            return;
        }

        $arrNotificationIds = $this->stringUtil->deserialize($eventConfig->get('eventBookingNotification'), true);

        if (!empty($arrNotificationIds)) {
            $this->notification->setTokens($eventConfig, $eventRegistration->getModel(), (int) $eventConfig->getModel()->eventBookingNotificationSender);
            $this->notification->notify($arrNotificationIds);
        }
    }
}
