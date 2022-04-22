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

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Helper\NotificationHelper;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;

/**
 * @Hook(Notification::HOOK, priority=Notification::PRIORITY)
 */
final class Notification extends AbstractHook
{
    public const HOOK = 'calEvtBookingPostBooking';
    public const PRIORITY = 1000;

    private NotificationHelper $notificationHelper;

    public function __construct(NotificationHelper $notificationHelper)
    {
        $this->notificationHelper = $notificationHelper;
    }

    /**
     * Run post booking notification.
     *
     * @throws \Exception
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $this->notificationHelper->notify($moduleInstance->getProperty('objEventMember'), $moduleInstance->getProperty('objEvent'));
    }
}
