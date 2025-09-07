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

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Markocupic\CalendarEventBookingBundle\CheckoutHandler\CheckoutHandlerAwareTrait;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(EventBookingCheckoutController::TYPE, category: 'events')]
class EventBookingCheckoutController extends AbstractFrontendModuleController
{
    use CheckoutHandlerAwareTrait;

    public const TYPE = 'event_booking_checkout';

    private CalendarEventsModel|null $event = null;

    private CalendarEventsMemberModel|null $booking = null;

    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        #[AutowireLocator('cebb.checkout_handler', defaultIndexMethod: 'getType')]
        private readonly ContainerInterface $checkoutHandlers,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            if (!$this->initialize($request)) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

        // Call the parent method
        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * @throws \Exception
     */
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->set('checkout', $this->checkoutHandler->getCheckoutData($this->booking, $model, $request));
        $template->set('booking', $this->booking);
        $template->set('event', $this->event);

        return $template->getResponse();
    }

    private function getBookingFromRequest(Request $request): CalendarEventsMemberModel|null
    {
        if (!$request->query->get('bookingToken')) {
            return null;
        }

        if (null === ($booking = $this->getContaoAdapter(CalendarEventsMemberModel::class)->findOneByBookingToken($request->query->get('bookingToken')))) {
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

    private function initialize(Request $request): bool
    {
        if (!$this->isCheckout($request)) {
            return false;
        }

        $this->booking = $this->getBookingFromRequest($request);
        $this->event = $this->booking?->getRelated('pid');
        $calendar = $this->event?->getRelated('pid');

        if (null === $this->booking || null === $this->event || !$this->event->published || null === $calendar) {
            return false;
        }

        $request->attributes->set('_calendar_event_booking_token', $this->booking->bookingToken);

        $this->setCheckoutHandler($this->checkoutHandlers, $calendar->eventBookingCheckoutHandler);

        return null !== $this->checkoutHandler;
    }
}
