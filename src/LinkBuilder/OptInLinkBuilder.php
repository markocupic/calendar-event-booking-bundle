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
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingOptInController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

class OptInLinkBuilder
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly UrlParser $urlParser,
    ) {
    }

    public function build(CalendarEventsMemberModel $booking, string $token): string
    {
        if (null === ($event = $booking->getRelated('pid'))) {
            throw new \Exception('Event not found.');
        }

        if (null === ($calendar = $event->getRelated('pid'))) {
            throw new \Exception('Calendar not found.');
        }

        if (!$calendar->requireOptIn) {
            return '';
        }

        if (null === ($page = $this->framework->getAdapter(PageModel::class)->findById($calendar->eventBookingOptInPage))) {
            return '';
        }

        // The token will be generated dynamically by the AddOptInTokenStampListener
        $params = \sprintf('action=%s&token=%s', EventBookingOptInController::ACTION, $token);

        return $this->urlParser->addQueryString($params, $page->getAbsoluteUrl());
    }
}
