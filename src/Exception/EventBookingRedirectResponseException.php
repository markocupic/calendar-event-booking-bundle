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

namespace Markocupic\CalendarEventBookingBundle\Exception;

use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EventBookingRedirectResponseException extends ResponseException
{
    public function __construct(string $location, int $status = 303, \Exception|null $previous = null)
    {
        parent::__construct(new RedirectResponse($location, $status), $previous);
    }
}
