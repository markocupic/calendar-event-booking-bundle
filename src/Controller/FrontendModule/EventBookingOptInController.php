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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Message;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Event\BookingConfirmEvent;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingOptInException;
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

#[AsFrontendModule(EventBookingOptInController::TYPE, category: 'events', template: 'mod_event_booking_opt_in')]
class EventBookingOptInController extends AbstractFrontendModuleController
{
    public const TYPE = 'event_booking_opt_in';

    public const ACTION = 'opt-in';

    private const TRANS_DOMAIN = 'mc_calendar_event_booking';

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LockFactory $lockFactory,
        private readonly NotificationCenter $notificationCenter,
        private readonly NotificationHelper $notificationHelper,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface|null $contaoErrorLogger,
        private readonly LoggerInterface|null $contaoGeneralLogger,
    ) {
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $message = $this->framework->getAdapter(Message::class);

        $uuid = $request->query->get('bookingToken');
        $action = $request->query->get('action');

        if (self::ACTION !== $action || empty($uuid)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $lock = $this->lockFactory->createLock(base64_encode(self::class.$uuid));
        $lock->acquire(true);

        $this->connection->beginTransaction();

        try {
            if (null === ($booking = CalendarEventsMemberModel::findOneByBookingToken($uuid))) {
                throw new EventBookingOptInException('Booking not found.', $this->translator->trans('mod_opt_in.error.booking_not_found', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            $template->booking = $booking;

            if (!$event = CalendarEventsModel::findById((int) $booking->pid)) {
                throw new EventBookingOptInException('Event not found.', $this->translator->trans('mod_opt_in.error.corresponding_event_not_found', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            $template->event = $event;

            if (!$calendar = $event->getRelated('pid')) {
                throw new EventBookingOptInException('Calendar not found.', $this->translator->trans('mod_opt_in.error.corresponding_calendar_not_found', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            $template->calendar = $calendar;

            if ($this->processConfirm($template, $calendar, $event, $booking, $request)) {
                // Send notification
                if ($calendar->optInNotification) {
                    $tokens = $this->notificationHelper->getNotificationTokens($booking);
                    $this->notificationCenter->sendNotification($calendar->optInNotification, $tokens);
                }
            }

            $this->connection->commit();
        } catch (EventBookingOptInException $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $message->add($e->getTranslatableText(), $e->getSeverityLevel());
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $message->addError($this->translator->trans('mod_opt_in.error.unexpected_error', [], self::TRANS_DOMAIN));

            if (method_exists($e, 'getMessage')) {
                $this->contaoErrorLogger?->error($e->getMessage());
            }
        } finally {
            $lock->release();
        }

        $template->messages = $message->hasMessages() ? $message->generate('FE') : null;

        return $template->getResponse();
    }

    private function processConfirm(Template $template, CalendarModel $calendar, CalendarEventsModel $calendarEvent, CalendarEventsMemberModel $booking, Request $request): bool
    {
        $message = $this->framework->getAdapter(Message::class);

        // Check if already canceled
        if ($booking->canceled) {
            $template->class .= ' error booking-canceled';
            $template->alreadyCanceled = true;

            throw new EventBookingOptInException('Booking canceled.', $this->translator->trans('mod_opt_in.error.booking_canceled', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
        }

        // Check if already confirmed (optIn === true)
        if ($booking->optIn) {
            $template->class .= ' info already-confirmed';
            $template->alreadyConfirmed = true;

            throw new EventBookingOptInException('Booking already confirmed.', $this->translator->trans('mod_opt_in.info.already_confirmed', [], self::TRANS_DOMAIN));
        }

        // Check if opt-in is required
        if (!$calendar->requireOptIn) {
            $template->class .= ' info confirm-not-required';
            $template->confirmNotRequired = true;

            throw new EventBookingOptInException('Opt-In not required.', $this->translator->trans('mod_opt_in.info.opt_in_not_required', [], self::TRANS_DOMAIN), SeverityLevel::INFO);
        }

        // Check if booking has been expired
        if ($booking->expired) {
            $template->class .= ' error confirm-expired';
            $template->confirmExpired = true;

            throw new EventBookingOptInException('Booking already expired.', $this->translator->trans('mod_opt_in.error.confirm_expired', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
        }

        // Check if past even start date
        if (!empty($calendarEvent->startDate) && time() > $calendarEvent->startDate) {
            $template->class .= ' error confirm-no-more-possible';
            $template->cannotConfirm = true;

            throw new EventBookingOptInException('Confirm no more possible.', $this->translator->trans('mod_opt_in.error.confirm_no_more_possible', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
        }

        // Check if past booking date
        if (!empty($calendarEvent->bookingEndDate) && time() > $calendarEvent->bookingEndDate) {
            $template->class .= ' error confirm-no-more-possible';
            $template->cannotConfirm = true;

            throw new EventBookingOptInException('Confirm no more possible.', $this->translator->trans('mod_opt_in.error.confirm_no_more_possible', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
        }

        $booking->optIn = true;
        $booking->temporaryReserved = false;
        $booking->save();
        $template->class .= ' confirm-success';
        $message->addInfo($this->translator->trans('mod_opt_in.info.opt_in_success', [], self::TRANS_DOMAIN));

        $event = new BookingConfirmEvent($booking, self::class, $request);
        $this->eventDispatcher->dispatch($event);

        $this->contaoGeneralLogger?->info(\sprintf('Booking for "%s" ID: %d confirmed via link.', $calendarEvent->title, $booking->id));

        return true;
    }
}
