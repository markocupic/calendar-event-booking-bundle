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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PostUnsubscribe;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\StringUtil;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber\EventSubscriber;
use Markocupic\CalendarEventBookingBundle\EventBooking\Helper\Formatter;
use Markocupic\CalendarEventBookingBundle\EventBooking\Notification\Notification;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

/**
 * @Hook(SendNotification::HOOK, priority=SendNotification::PRIORITY)
 */
final class SendNotification extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_UNSUBSCRIBE_FROM_EVENT;
    public const PRIORITY = 1000;

    private ContaoFramework $framework;
    private Notification $notification;

    // Adapters
    private Adapter $stringUtil;

    public function __construct(ContaoFramework $framework, Notification $notification)
    {
        $this->framework = $framework;
        $this->notification = $notification;

        // Adapters
        $this->stringUtil = $this->framework->getAdapter(StringUtil::class);
    }

    /**
     * @param EventConfig $eventConfig
     * @param EventSubscriber $eventSubscriber
     * @return void
     * @throws \Exception
     */
    public function __invoke(EventConfig $eventConfig, EventSubscriber $eventSubscriber): void
    {
        if (!self::isEnabled()) {
            return;
        }

        // Multiple notifications possible
        $arrNotificationIds = $this->stringUtil->deserialize($eventConfig->getModel()->eventUnsubscribeNotification, true);

        if (!empty($arrNotificationIds)) {
            // Get notification tokens
            $this->notification->setTokens($eventConfig, $eventSubscriber->getModel(), (int) $eventConfig->getModel()->eventUnsubscribeNotificationSender);
            $this->notification->notify($arrNotificationIds);
        }
    }
}
