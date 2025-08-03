<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class EventBookingResponseException extends \RuntimeException
{
    public function __construct(
        private readonly Response $response,
        \Exception|null $previous = null,
    ) {
        parent::__construct('This exception has no message. Use $exception->getResponse() instead.', 0, $previous);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
