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

use Codefog\HasteBundle\UrlParser;
use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Event\CancelBookingEvent;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingUnsubscribeException;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Helper\NotificationHelper;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

#[AsFrontendModule(EventBookingUnsubscribeController::TYPE, category: 'events', template: 'mod_event_booking_unsubscribe')]
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
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LockFactory $lockFactory,
        private readonly NotificationCenter $notificationCenter,
        private readonly NotificationHelper $notificationHelper,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
        private readonly UrlParser $urlParser,
        private readonly LoggerInterface|null $contaoErrorLogger,
        private readonly LoggerInterface|null $contaoGeneralLogger,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        if (!$page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            return parent::__invoke($request, $model, $section, $classes);
        }

        $page->noSearch = 1;

        if (self::ACTION !== $request->query->get(self::QUERY_PARAM_ACTION)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $bookingToken = $request->query->get(self::QUERY_PARAM_BOOKING_TOKEN, false);

        if (empty($bookingToken)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * @throws \Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $message = $this->framework->getAdapter(Message::class);

        $uuid = $request->query->get(self::QUERY_PARAM_BOOKING_TOKEN, false);

        $lock = $this->lockFactory->createLock(base64_encode(self::class.$uuid));
        $lock->acquire(true);

        $this->connection->beginTransaction();

        try {
            $booking = CalendarEventsMemberModel::findOneByBookingToken($uuid);

            if (null === $booking) {
                $template->class = ' error booking-not-found';

                throw new EventBookingUnsubscribeException('Booking not found.', $this->translator->trans('mod_unsubscribe.error.invalid_uuid', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            $template->booking = $booking;

            $event = $booking->getRelated('pid');

            if (null === $event) {
                $template->class = ' error event-not-found';

                throw new EventBookingUnsubscribeException('Event not found.', $this->translator->trans('mod_unsubscribe.error.event_not_found', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            $template->event = $event;
            $template->calendar = $event->getRelated('pid');

            if ($booking->canceled) {
                $this->hasUnsubscribed = true;
                $template->hasUnsubscribed = true;
                $template->class = ' info booking-already-canceled';

                if ('true' === $request->query->get(self::QUERY_PARAM_UNSUBSCRIBED)) {
                    throw new EventBookingUnsubscribeException('You have unsubscribed.', $this->translator->trans('mod_unsubscribe.info.unsubscribe_success', ['%title%' => $event->title], self::TRANS_DOMAIN), SeverityLevel::INFO);
                }

                throw new EventBookingUnsubscribeException('You have unsubscribed.', $this->translator->trans('mod_unsubscribe.info.already_unsubscribed', ['%title%' => $event->title], self::TRANS_DOMAIN), SeverityLevel::INFO);
            }

            if (!$event->enableDeregistration) {
                $template->class = ' error unsubscription-not-allowed';

                throw new EventBookingUnsubscribeException('Unsubscription not allowed.', $this->translator->trans('mod_unsubscribe.error.unsubscription_not_allowed', ['%title%' => $event->title], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            if ($this->isUnsubscriptionLimitExpired($event)) {
                $template->class = ' error unsubscription-limit-expired';

                throw new EventBookingUnsubscribeException('Unsubscription limit has expired.', $this->translator->trans('mod_unsubscribe.error.unsubscription_limit_expired', ['%title%' => $event->title], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            if (self::FORM_ID === $request->request->get('FORM_SUBMIT')) {
                $this->processUnsubscription($booking, $event, $request);
                $this->connection->commit();
                $redirectUrl = $this->urlParser->addQueryString(self::QUERY_PARAM_UNSUBSCRIBED.'=true');
                $this->framework->getAdapter(Controller::class)->redirect($redirectUrl);
            }

            $template->hasForm = true;
            $template->formId = 'tl_unsubscribe_from_event';
            $template->requestToken = $this->csrfTokenManager->getDefaultTokenValue();
            $this->connection->commit();
        } catch (EventBookingUnsubscribeException $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $message->add($e->getTranslatableText(), $e->getSeverityLevel());
        } catch (RedirectResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $message->addError($this->translator->trans('mod_unsubscribe.error.unexpected_error', [], self::TRANS_DOMAIN));

            if (method_exists($e, 'getMessage')) {
                $this->contaoErrorLogger?->error($e->getMessage());
            }
        } finally {
            $lock->release();
        }

        $template->messages = Message::generate('FE');

        return $template->getResponse();
    }

    /**
     * @throws \Exception
     */
    private function notify(CalendarEventsMemberModel $booking, CalendarEventsModel $event): void
    {
        $calendar = $event->getRelated('pid');

        if (!$calendar?->unsubscribeNotification) {
            return;
        }

        $tokens = $this->notificationHelper->getNotificationTokens($booking);
        $this->notificationCenter->sendNotification($calendar->unsubscribeNotification, $tokens);
    }

    private function isUnsubscriptionLimitExpired(CalendarEventsModel $event): bool
    {
        if (!empty($event->unsubscribeLimitTstamp)) {
            return strtotime('now') > $event->unsubscribeLimitTstamp;
        }

        $limitDays = !$event->unsubscribeLimit > 0 ? 0 : $event->unsubscribeLimit;
        $limitTimestamp = $limitDays * 3600 * 24;

        if ($event->addTime && $event->startTime > $event->startDate) {
            return strtotime('now') + $limitTimestamp > $event->startTime;
        }

        return strtotime('today 00:00') + $limitTimestamp > $event->startDate;
    }

    private function processUnsubscription(CalendarEventsMemberModel $booking, CalendarEventsModel $calendarEvent, Request $request): void
    {
        $booking->canceled = true;
        $booking->temporaryReserved = false;
        $booking->save();

        $event = new CancelBookingEvent($booking, self::class, $request);
        $this->eventDispatcher->dispatch($event);

        $this->notify($booking, $calendarEvent);
        $this->contaoGeneralLogger?->info(\sprintf('Booking for event "%s" ID %d has been unsubscribed by link.', $calendarEvent->title, $booking->id));
    }
}
