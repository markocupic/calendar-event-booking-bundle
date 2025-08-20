<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Markocupic\CalendarEventBookingBundle\Helper\WaitingListManager;

#[AsCronJob('minutely')]
class CheckWaitingListCron
{
    public function __construct(
        private readonly WaitingListManager $waitingListManager,
    ) {
    }

    public function __invoke(): void
    {
        $this->waitingListManager->checkWaitingList();
    }
}
