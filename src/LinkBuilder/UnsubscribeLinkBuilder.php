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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingUnsubscribeController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class UnsubscribeLinkBuilder
{
    public function __construct(
        private ContaoFramework $framework,
        private ContentUrlGenerator $contentUrlGenerator,
        private UrlParser $urlParser,
    ) {
    }

    public function build(CalendarEventsMemberModel $booking): string
    {
        if (null === ($event = $booking->getRelated('pid'))) {
            throw new \Exception('Event not found.');
        }

        if (!$event->enableDeregistration) {
            return '';
        }

        if (null === ($calendar = $event->getRelated('pid'))) {
            throw new \Exception('Calendar not found.');
        }

        if (null === ($page = $this->framework->getAdapter(PageModel::class)->findById($calendar->eventUnsubscribePage))) {
            return '';
        }

        $params = \sprintf('action=%s&bookingToken=%s', EventBookingUnsubscribeController::ACTION, $booking->bookingToken);

        return $this->urlParser->addQueryString($params, $this->contentUrlGenerator->generate($page, [], UrlGeneratorInterface::ABSOLUTE_URL));
    }
}
