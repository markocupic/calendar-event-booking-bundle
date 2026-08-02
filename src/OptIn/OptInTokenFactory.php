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

namespace Markocupic\CalendarEventBookingBundle\OptIn;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\OptIn\OptInToken;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\OptInModel;

/**
 * Wraps the creation of an OptInToken so that the opt-in confirmation flow can be
 * unit tested by injecting a token double instead of instantiating OptInToken
 * directly inside the controller.
 */
class OptInTokenFactory
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function create(OptInModel $optInModel): OptInTokenInterface
    {
        return new OptInToken($optInModel, $this->framework);
    }
}
