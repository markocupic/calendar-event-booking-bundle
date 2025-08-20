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

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\FrontendUser;

class FrontendUserManager
{
    public function __construct(
        private readonly TokenChecker $tokenChecker,
    ) {
    }

    public function hasLoggedInFrontendUser(): bool
    {
        return $this->tokenChecker->hasFrontendUser();
    }

    public function getLoggedInFrontendUser(): FrontendUser|null
    {
        if (!$this->tokenChecker->hasFrontendUser()) {
            return null;
        }

        return $this->getFrontendUserInstance();
    }

    private function getFrontendUserInstance(): FrontendUser
    {
        return FrontendUser::getInstance();
    }
}
