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
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Event\CancelBookingEvent;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingUnsubscribeException;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Helper\NotificationManager;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Markocupic\ContaoFlashMessage\FlashMessage\MessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
        private readonly NotificationManager $notificationManager,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
        private readonly UrlParser $urlParser,
        private readonly LoggerInterface|null $contaoErrorLogger,
        private readonly LoggerInterface|null $contaoGeneralLogger,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        if (!$page instanceof PageModel && !$this->scopeMatcher->isFrontendRequest($request)) {
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
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $uuid = $request->query->get(self::QUERY_PARAM_BOOKING_TOKEN, false);

        $lock = $this->lockFactory->createLock(base64_encode(self::class.$uuid));
        $lock->acquire(true);

        $this->connection->beginTransaction();

        try {
            $booking = CalendarEventsMemberModel::findOneByBookingToken($uuid);

            if (null === $booking) {
                $this->addCssClassToTemplate('error booking-not-found', $template);

                throw new EventBookingUnsubscribeException('Booking not found.', $this->translator->trans('mod_unsubscribe.error.invalid_uuid', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            $template->set('booking', $booking);

            $event = $booking->getRelated('pid');

            if (!$event instanceof CalendarEventsModel) {
                $this->addCssClassToTemplate('error event-not-found', $template);

                throw new EventBookingUnsubscribeException('Event not found.', $this->translator->trans('mod_unsubscribe.error.event_not_found', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            $template->set('event', $event);
            $template->set('calendar', $event->getRelated('pid'));

            if ($booking->canceled) {
                $template->set('hasUnsubscribed', true);
                $this->addCssClassToTemplate('info booking-already-canceled', $template);

                if ('true' === $request->query->get(self::QUERY_PARAM_UNSUBSCRIBED)) {
                    throw new EventBookingUnsubscribeException('You have unsubscribed.', $this->translator->trans('mod_unsubscribe.info.unsubscribe_success', ['%title%' => $event->title], self::TRANS_DOMAIN), SeverityLevel::INFO);
                }

                throw new EventBookingUnsubscribeException('You have unsubscribed.', $this->translator->trans('mod_unsubscribe.info.already_unsubscribed', ['%title%' => $event->title], self::TRANS_DOMAIN), SeverityLevel::INFO);
            }

            if (!$event->enableDeregistration) {
                $this->addCssClassToTemplate('error unsubscription-not-allowed', $template);

                throw new EventBookingUnsubscribeException('Unsubscription not allowed.', $this->translator->trans('mod_unsubscribe.error.unsubscription_not_allowed', ['%title%' => $event->title], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            if ($this->isUnsubscriptionLimitExpired($event)) {
                $this->addCssClassToTemplate('error unsubscription-limit-expired', $template);

                throw new EventBookingUnsubscribeException('Unsubscription limit has expired.', $this->translator->trans('mod_unsubscribe.error.unsubscription_limit_expired', ['%title%' => $event->title], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            if (self::FORM_ID === $request->request->get('FORM_SUBMIT')) {
                $this->processUnsubscription($booking, $event, $request);
                $this->connection->commit();
                $redirectUrl = $this->urlParser->addQueryString(self::QUERY_PARAM_UNSUBSCRIBED.'=true');
                $this->getContaoAdapter(Controller::class)->redirect($redirectUrl);
            }

            $template->set('hasForm', true);
            $template->set('formId', 'tl_unsubscribe_from_event');
            $template->set('requestToken', $this->csrfTokenManager->getDefaultTokenValue());

            $this->connection->commit();
        } catch (EventBookingUnsubscribeException $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $this->message->add($e->getTranslatableText(), $e->getSeverityLevel());
        } catch (RedirectResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $this->message->addError($this->translator->trans('mod_unsubscribe.error.unexpected_error', [], self::TRANS_DOMAIN));

            if (method_exists($e, 'getMessage')) {
                $this->contaoErrorLogger?->error($e->getMessage());
            }
        } finally {
            $lock->release();
        }

        $template->set('messages', $this->message->hasMessages() ? $this->message->getAll() : null);

        if ($model->ceb_addImage && $event->addImage) {
            $figure = $this->figureUtil->buildFigure($event->row());

            if (null !== $figure) {
                $template->set('addImage', true);
                $template->set('figure', $figure);
            }
        }

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

        $tokens = $this->notificationManager->getNotificationTokens($booking);
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

        $request->attributes->set('_calendar_event_booking_token', $booking->bookingToken);

        $event = new CancelBookingEvent($booking, self::class, $request);
        $this->eventDispatcher->dispatch($event);

        $this->notify($booking, $calendarEvent);
        $this->contaoGeneralLogger?->info(\sprintf('Booking for event "%s" ID %d has been unsubscribed by link.', $calendarEvent->title, $booking->id));
    }

    private function addCssClassToTemplate(string $cssClass, FragmentTemplate $template): void
    {
        $classes = $template->get('element_css_classes').' '.$cssClass;
        $template->set('element_css_classes', implode(' ', array_filter(explode(' ', $classes))));
    }
}
