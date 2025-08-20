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

namespace Markocupic\CalendarEventBookingBundle\LinkBuilder;

use Codefog\HasteBundle\UrlParser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingUnsubscribeController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

class UnsubscribeLinkBuilder
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly UrlParser $urlParser,
    ) {
    }

    public function build(CalendarEventsMemberModel $booking): string
    {
        if (null === ($event = $booking->getRelated('pid'))) {
            return '';
        }

        if (!$event->enableDeregistration) {
            return '';
        }

        if (null === ($calendar = $event->getRelated('pid'))) {
            return '';
        }

        if (null === ($page = $this->framework->getAdapter(PageModel::class)->findById($calendar->eventUnsubscribePage))) {
            return '';
        }

        $params = \sprintf('action=%s&bookingToken=%s', EventBookingUnsubscribeController::ACTION, $booking->bookingToken);

        return $this->urlParser->addQueryString($params, $page->getAbsoluteUrl());
    }
}
