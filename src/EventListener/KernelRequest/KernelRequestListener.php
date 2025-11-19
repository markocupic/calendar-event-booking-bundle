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

namespace Markocupic\CalendarEventBookingBundle\EventListener\KernelRequest;

use Codefog\HasteBundle\UrlParser;
use Contao\Controller;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener]
class KernelRequestListener
{
    public function __construct(
        private readonly UrlParser $urlParser,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $flash = $request->getSession()->getFlashBag();

        $bookingToken = $flash->get('_calendar_event_booking_token');

        if (empty($bookingToken[0])) {
            return;
        }

        Controller::redirect($this->urlParser->addQueryString('bookingToken='.$bookingToken[0]));
    }
}

// src/EventListener/ResponseListener.php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // Disable bfcache by setting headers
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
    }
}
