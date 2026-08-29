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
use Markocupic\CalendarEventBookingBundle\Checkout\CheckoutHandlerAwareTrait;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Markocupic\ContaoFlashMessage\FlashMessage\MessageInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
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

    public const string TYPE = 'event_booking_checkout';

    private CalendarEventsModel|null $calEvent = null;

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
        private readonly LoggerInterface|null $contaoErrorLogger,
        private readonly LoggerInterface|null $contaoGeneralLogger,
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

            // Add messages to template
            $template->set('messagesUnwrapped', $this->message->renderUnwrapped(peek: true));
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
        $template->set('event', $this->calEvent);
        $template->set('calendar', $this->calEvent->getRelated('pid')->current());

        // Add messages to template
        $template->set('messagesUnwrapped', $this->message->renderUnwrapped(peek: true));
        $template->set('messages', $this->message->hasMessages() ? $this->message->getAll() : null);

        if ($model->ceb_addImage && $this->calEvent->addImage) {
            $figure = $this->figureUtil->buildFigure($this->calEvent->row());

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

    /**
     * Resolves the booking, its event and its calendar, or says why it cannot.
     *
     * Every reason ends in the same sentence for the visitor - "booking not
     * found" - which is right for them and useless for whoever has to explain it
     * afterwards. So each reason is written to the log with the booking token,
     * which is the only thing there is to look anything up by.
     *
     * Two channels, because the reasons are not of one kind. A booking without an
     * event, or an event without a calendar, means the data no longer hangs
     * together: that is an error. An expired link, an unpublished event, a
     * calendar whose booking was switched off - those are ordinary and belong in
     * the general log, where they do not bury the ones worth chasing.
     *
     * A request without a token at all is not logged. It is not a failed checkout
     * but no checkout, and this module sits on a page that search engines and
     * link checkers visit like any other. Logging that would fill the log with
     * page views.
     */
    private function initialize(Request $request): bool
    {
        $bookingToken = (string) $request->query->get('bookingToken', '');

        if ('' === $bookingToken) {
            return false;
        }

        $this->booking = $this->getBookingFromRequest($request);

        if (null === $this->booking) {
            $this->contaoGeneralLogger?->info(\sprintf('The event booking checkout was opened with the booking token "%s", but no booking carries that token. It may have been removed by the auto delete cron.', $bookingToken));

            return false;
        }

        $this->calEvent = $this->booking->getRelated('pid');

        if (null === $this->calEvent) {
            $this->contaoErrorLogger?->error(\sprintf('The event booking checkout could not resolve the event of booking ID %s (token "%s").', $this->booking->id, $bookingToken));

            return false;
        }

        if (!$this->calEvent->published) {
            $this->contaoGeneralLogger?->info(\sprintf('The event booking checkout was opened for booking ID %s, but event ID %s is not published.', $this->booking->id, $this->calEvent->id));

            return false;
        }

        $this->calendar = $this->calEvent->getRelated('pid');

        if (null === $this->calendar) {
            $this->contaoErrorLogger?->error(\sprintf('The event booking checkout could not resolve the calendar of event ID %s (booking ID %s).', $this->calEvent->id, $this->booking->id));

            return false;
        }

        if (!$this->calendar->allowEventBooking) {
            $this->contaoGeneralLogger?->info(\sprintf('The event booking checkout was opened for booking ID %s, but event booking is switched off on calendar ID %s.', $this->booking->id, $this->calendar->id));

            return false;
        }

        $request->attributes->set('_calendar_event_booking_token', $this->booking->bookingToken);

        $this->setCheckoutHandler($this->resolveCheckoutHandler($this->checkoutHandlers, $this->calendar->eventBookingCheckoutHandler));

        return null !== $this->checkoutHandler;
    }
}
