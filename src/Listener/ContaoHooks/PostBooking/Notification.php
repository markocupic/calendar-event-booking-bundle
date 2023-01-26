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

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Helper\NotificationHelper;

#[AsHook(Notification::HOOK, priority: 1000)]
final class Notification
{
    public const HOOK = 'calEvtBookingPostBooking';

    public function __construct(
        private readonly NotificationHelper $notificationHelper,
    ) {
    }

    /**
     * Run post booking notification.
     *
     * @throws \Exception
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance, array $arrDisabledHooks = []): void
    {
        if (\in_array(self::class, $arrDisabledHooks, true)) {
            return;
        }

        $this->notificationHelper->notify($moduleInstance->getProperty('objEventMember'), $moduleInstance->getProperty('objEvent'));
    }
}
