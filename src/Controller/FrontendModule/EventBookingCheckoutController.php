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

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\CheckoutHandler\CheckoutHandlerAwareTrait;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(EventBookingCheckoutController::TYPE, category: 'events', template: 'mod_event_booking_checkout')]
class EventBookingCheckoutController extends AbstractFrontendModuleController
{
    use CheckoutHandlerAwareTrait;

    public const TYPE = 'event_booking_checkout';

    private CalendarModel|null $calendar = null;

    private CalendarEventsModel|null $event = null;

    private CalendarEventsMemberModel|null $booking = null;

    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        #[TaggedIterator('cebb.checkout_handler')]
        private readonly iterable $checkoutHandlers,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            if (!$this->isCheckout($request)) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }

            $this->booking = $this->getBookingFromRequest($request);
            $this->event = $this->booking?->getRelated('pid');
            $this->calendar = $this->event?->getRelated('pid');

            if (null === $this->event || !$this->event->published || null === $this->calendar) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }

            $this->setCheckoutHandler($this->checkoutHandlers, $this->calendar->eventBookingCheckoutHandler);

            if (null === $this->checkoutHandler) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

        // Call the parent method
        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * @throws \Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $template->checkout = $this->checkoutHandler->getCheckoutData($this->booking, $model, $request);
        $template->booking = $this->booking;
        $template->event = $this->event;

        return $template->getResponse();
    }

    private function getBookingFromRequest(Request $request): CalendarEventsMemberModel|null
    {
        if (!$request->query->get('bookingToken')) {
            return null;
        }

        if (null === ($booking = CalendarEventsMemberModel::findOneByBookingToken($request->query->get('bookingToken')))) {
            return null;
        }

        return $booking;
    }

    private function isCheckout(Request $request): bool
    {
        if (null !== $this->getBookingFromRequest($request)) {
            return true;
        }

        return false;
    }
}
