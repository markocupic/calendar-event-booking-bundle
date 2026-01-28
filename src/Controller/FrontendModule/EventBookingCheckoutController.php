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
use Contao\CalendarModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Markocupic\CalendarEventBookingBundle\CheckoutHandler\CheckoutHandlerAwareTrait;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Markocupic\ContaoFlashMessage\FlashMessage\MessageInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(EventBookingCheckoutController::TYPE, category: 'events')]
class EventBookingCheckoutController extends AbstractFrontendModuleController
{
    use CheckoutHandlerAwareTrait;

    public const TYPE = 'event_booking_checkout';

    private CalendarEventsModel|null $event = null;

    private CalendarModel|null $calendar = null;

    private CalendarEventsMemberModel|null $booking = null;

    public function __construct(
        #[AutowireLocator('cebb.checkout_handler', defaultIndexMethod: 'getType')]
        private readonly ContainerInterface $checkoutHandlers,
        private readonly FigureUtil $figureUtil,
        #[Autowire(service: 'markocupic_calendar_event_booking.flash_message.checkout')]
        private readonly MessageInterface $message,
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $page = $this->getPageModel();
        $page->alwaysLoadFromCache = false;
        $page->cache = 0;

        if (!$this->initialize($request)) {
            $errorMessage = $this->translator->trans('mod_checkout.error.booking_not_found', [], 'mc_calendar_event_booking');
            $this->message->addError($errorMessage);
            $template->set('hasInitializationError', true);
            $template->set('messages', $this->message->hasMessages() ? $this->message->getAll() : null);

            // Stop here if initialization fails.
            return $template->getResponse();
        }

        if ($model->ceb_modCheckout_handler !== $this->calendar->eventBookingCheckoutHandler) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $checkoutResult = $this->checkoutHandler->handleRequest($this->booking, $model, $request);

        // If the checkout handler returns a response (e.g. RedirectResponse), we don't
        // need to render the template.
        if ($checkoutResult->hasResponse()) {
            return $checkoutResult->getResponse();
        }

        $template->set('checkout', $checkoutResult);
        $template->set('booking', $this->booking);
        $template->set('event', $this->event);
        $template->set('calendar', $this->event->getRelated('pid')->current());

        if ($model->ceb_addImage && $this->event->addImage) {
            $figure = $this->figureUtil->buildFigure($this->event->row());

            if (null !== $figure) {
                $template->set('addImage', true);
                $template->set('figure', $figure);
            }
        }

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
        $this->calendar = $this->event?->getRelated('pid');

        if (null === $this->booking || null === $this->event || !$this->event->published || null === $this->calendar) {
            return false;
        }

        $request->attributes->set('_calendar_event_booking_token', $this->booking->bookingToken);

        $this->setCheckoutHandler($this->resolveCheckoutHandler($this->checkoutHandlers, $this->calendar->eventBookingCheckoutHandler));

        return null !== $this->checkoutHandler;
    }
}
