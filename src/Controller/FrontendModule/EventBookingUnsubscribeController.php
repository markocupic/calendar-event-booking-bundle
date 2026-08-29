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

use Codefog\HasteBundle\UrlParser;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Markocupic\CalendarEventBookingBundle\Domain\Unsubscribe\UnsubscribeValidator;
use Markocupic\CalendarEventBookingBundle\Event\CancelBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Markocupic\CalendarEventBookingBundle\Util\LogBuilder;
use Markocupic\ContaoFlashMessage\FlashMessage\MessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

#[AsFrontendModule(EventBookingUnsubscribeController::TYPE, category: 'events')]
class EventBookingUnsubscribeController extends AbstractFrontendModuleController
{
    public const TYPE = 'event_booking_unsubscribe';

    public const ACTION = 'unsubscribe';

    private const FORM_ID = 'tl_unsubscribe_from_event';

    private const QUERY_PARAM_ACTION = 'action';

    private const QUERY_PARAM_BOOKING_TOKEN = 'bookingToken';

    private const QUERY_PARAM_UNSUBSCRIBED = 'hasUnsubscribed';

    private const TRANS_DOMAIN = 'mc_calendar_event_booking';

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly FigureUtil $figureUtil,
        private readonly LockFactory $lockFactory,
        #[Autowire(service: 'markocupic_calendar_event_booking.flash_message.unsubscribe')]
        private readonly MessageInterface $message,
        private readonly NotificationCenter $notificationCenter,
        private readonly NotificationService $notificationService,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
        private readonly UrlParser $urlParser,
        private readonly UnsubscribeValidator $validator,
        private readonly LoggerInterface|null $contaoErrorLogger,
        private readonly LoggerInterface|null $contaoGeneralLogger,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return parent::__invoke($request, $model, $section, $classes);
        }

        if (null !== $page) {
            $page->noSearch = 1;
        }

        if (self::ACTION !== $request->query->get(self::QUERY_PARAM_ACTION)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $bookingToken = $request->query->get(self::QUERY_PARAM_BOOKING_TOKEN, '');

        if (empty($bookingToken)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $bookingToken = $request->query->get(self::QUERY_PARAM_BOOKING_TOKEN, '');
        $booking = $this->getContaoAdapter(CalendarEventsMemberModel::class)->findOneByBookingToken($bookingToken);

        $unsubscribedFlag = 'true' === $request->query->get(self::QUERY_PARAM_UNSUBSCRIBED);

        $result = $this->validator->validate($booking, $unsubscribedFlag, self::TRANS_DOMAIN);

        if ($result->isError()) {
            $this->addCssClassToTemplate((string) $result->cssClass, $template);
            $this->message->add($result->message, $result->severity);
            $template->set('messagesUnwrapped', $this->message->renderUnwrapped(peek: true));
            $template->set('messages', $this->message->hasMessages() ? $this->message->getAll() : null);

            return $template->getResponse();
        }

        $calEvent = $result->value;

        $template->set('booking', $booking);
        $template->set('event', $calEvent);
        $template->set('calendar', $calEvent->getRelated('pid'));
        $template->set('hasForm', true);
        $template->set('formId', self::FORM_ID);
        $template->set('requestToken', $this->csrfTokenManager->getDefaultTokenValue());

        if (self::FORM_ID === $request->request->get('FORM_SUBMIT')) {
            $response = $this->handleFormSubmission($booking, $calEvent, $request, $bookingToken);

            if ($response instanceof Response) {
                return $response;
            }
        }

        $this->applyEventImage($model, $calEvent, $template);

        $template->set('messagesUnwrapped', $this->message->renderUnwrapped(peek: true));
        $template->set('messages', $this->message->hasMessages() ? $this->message->getAll() : null);

        return $template->getResponse();
    }

    private function handleFormSubmission(CalendarEventsMemberModel $booking, CalendarEventsModel $calEvent, Request $request, string $bookingToken): Response|null
    {
        $lock = $this->lockFactory->createLock(base64_encode(self::class.$bookingToken));
        $lock->acquire(true);

        try {
            // Reload the booking under the lock to stay idempotent against a concurrent
            // double-submit of the same token. Both the booking and the validator were
            // evaluated before the lock was acquired, so a parallel request may already
            // have canceled this booking in the meantime. If it is already canceled
            // (or gone), there is nothing left to do: redirect to the success page
            // without saving, dispatching the event or sending the notification again.
            $booking = $this->getContaoAdapter(CalendarEventsMemberModel::class)->findOneByBookingToken($bookingToken);

            if (null === $booking || $booking->canceled) {
                return new RedirectResponse($this->urlParser->addQueryString(self::QUERY_PARAM_UNSUBSCRIBED.'=true'));
            }

            // Only start the transaction once we know there is real work to do. Doing so
            // inside the try keeps both the lock (released in finally) and the transaction
            // (rolled back only when actually active) leak-free on any failure path.
            $this->connection->beginTransaction();

            $booking->canceled = true;
            $booking->temporaryReserved = false;
            $booking->log = LogBuilder::append((string) $booking->log, 'Unsubscribed by the participant via the unsubscribe form.');
            $booking->save();

            $request->attributes->set('_calendar_event_booking_token', $booking->bookingToken);

            $this->contaoGeneralLogger?->info(\sprintf('Booking for event "%s" ID %d has been unsubscribed by link.', $calEvent->title, $booking->id));

            $event = new CancelBookingEvent($booking, self::class, $request);
            $this->eventDispatcher->dispatch($event);

            // Send notifications
            $this->notify($booking, $calEvent);

            $this->connection->commit();

            if ($event->getResponse() instanceof Response) {
                return $event->getResponse();
            }

            return new RedirectResponse($this->urlParser->addQueryString(self::QUERY_PARAM_UNSUBSCRIBED.'=true'));
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            $this->message->addError($this->translator->trans('mod_unsubscribe.error.unexpected_error', [], self::TRANS_DOMAIN));
            $this->contaoErrorLogger?->error($e->getMessage());

            return null;
        } finally {
            $lock->release();
        }
    }

    private function applyEventImage(ModuleModel $model, CalendarEventsModel|null $calEvent, FragmentTemplate $template): void
    {
        if (!$model->ceb_addImage || null === $calEvent || !$calEvent->addImage) {
            return;
        }

        $figure = $this->figureUtil->buildFigure($calEvent->row());

        if (null !== $figure) {
            $template->set('addImage', true);
            $template->set('figure', $figure);
        }
    }

    /**
     * @throws \Exception
     */
    private function notify(CalendarEventsMemberModel $booking, CalendarEventsModel $calEvent): void
    {
        $calendar = $calEvent->getRelated('pid');

        if (!$calendar?->unsubscribeNotification) {
            return;
        }

        $tokens = $this->notificationService->getNotificationTokens($booking);
        $this->notificationCenter->sendNotification($calendar->unsubscribeNotification, $tokens);
    }

    private function addCssClassToTemplate(string $cssClass, FragmentTemplate $template): void
    {
        $classes = trim($template->get('element_css_classes').' '.$cssClass);
        $template->set('element_css_classes', implode(' ', array_filter(explode(' ', $classes))));
    }
}
