<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Subscriber\PostBooking;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Haste\Util\Url;
use Markocupic\CalendarEventBookingBundle\Event\PostBookingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RedirectSubscriber implements EventSubscriberInterface
{
    public const PRIORITY = 1000;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostBookingEvent::NAME => ['redirectOnFormSent', self::PRIORITY],
        ];
    }

    /**
     * Redirect to the jump to page on form sent.
     */
    public function redirectOnFormSent(PostBookingEvent $event): void
    {
        $objFormModel = $event->getFormModel();
        $objEventMember = $event->getEventMember();

        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $controllerAdapter = $this->framework->getAdapter(Controller::class);
        $urlAdapter = $this->framework->getAdapter(Url::class);

        // Redirect to the jumpTo page
        if ($objFormModel->jumpTo) {
            $objPageModel = $pageModelAdapter->findByPk($objFormModel->jumpTo);

            if (null !== $objPageModel) {
                $strRedirectUrl = $urlAdapter->addQueryString('bookingToken='.$objEventMember->bookingToken, $objPageModel->getFrontendUrl());
                $controllerAdapter->redirect($strRedirectUrl);
            }
        }
    }
}
