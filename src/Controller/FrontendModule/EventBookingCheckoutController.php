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
use Contao\Message;
use Contao\ModuleModel;
use Markocupic\CalendarEventBookingBundle\CheckoutHandler\CheckoutHandlerAwareTrait;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Container\ContainerInterface;
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

    private CalendarEventsMemberModel|null $booking = null;

    public function __construct(
        #[AutowireLocator('cebb.checkout_handler', defaultIndexMethod: 'getType')]
        private readonly ContainerInterface $checkoutHandlers,
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
            if (!$this->getContaoAdapter(Message::class)->hasError()) {
                $errorMessage = $this->translator->trans('mod_checkout.error.booking_not_found', [], 'mc_calendar_event_booking');
                $this->getContaoAdapter(Message::class)->addError($errorMessage);
            }

            $template->set('errorMessages', $this->getErrorMessages());

            // Stop here if initialization fails.
            return $template->getResponse();
        }

        $checkoutResponse = $this->checkoutHandler->getResponse($this->booking, $model, $request);

        // If the checkout handler returns a response (e.g. RedirectResponse), we don't
        // need to render the template.
        if ($checkoutResponse->hasResponse()) {
            return $checkoutResponse->getResponse();
        }

        $template->set('checkout', $checkoutResponse);
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

    private function getInfoMessages(): string|null
    {
        return $this->getMessages('info');
    }

    private function getConfirmMessages(): string|null
    {
        return $this->getMessages('confirm');
    }

    private function getErrorMessages(): string|null
    {
        return $this->getMessages('error');
    }

    private function getMessages(string $type): string|null
    {
        if (!\in_array($type, ['error', 'confirm', 'info'], true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid message type "%s".', $type));
        }

        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (!$session->isStarted()) {
            return null;
        }

        $messages = $session->getFlashBag()->get('contao.FE.'.$type);

        if (empty($messages)) {
            return null;
        }

        $messageString = '';

        foreach ($messages as $message) {
            $messageString .= \sprintf('<p class="tl_%s">%s</p>', $type, $message);
        }

        return $messageString;
    }
}
