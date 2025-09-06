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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\OptIn\OptInToken;
use Contao\CoreBundle\OptIn\OptInTokenAlreadyConfirmedException;
use Contao\CoreBundle\OptIn\OptInTokenNoLongerValidException;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Message;
use Contao\ModuleModel;
use Contao\OptInModel;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Event\BookingConfirmEvent;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingOptInException;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Helper\NotificationManager;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

#[AsFrontendModule(EventBookingOptInController::TYPE, category: 'events')]
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
        private readonly NotificationManager $notificationManager,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface|null $contaoErrorLogger,
        private readonly LoggerInterface|null $contaoGeneralLogger,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $message = $this->getContaoAdapter(Message::class);

        $token = $request->query->get('token');
        $action = $request->query->get('action');

        if (self::ACTION !== $action || empty($token)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        /** @var OptInModel $optInModel */
        $optInModel = OptInModel::findOneByToken($token);

        if (null === $optInModel) {
            throw new EventBookingOptInException('Confirm no more possible.', $this->translator->trans('mod_opt_in.error.confirm_no_more_possible', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
        }

        $optInToken = new OptInToken($optInModel, $this->framework);

        $lock = $this->lockFactory->createLock(base64_encode(self::class.$token));
        $lock->acquire(true);

        $this->connection->beginTransaction();

        try {
            // Try to confirm and invalidate the token. Will throw an exception if the token
            // is already confirmed or expired
            $optInToken->confirm();

            // Ok... the token is valid! Now check if the booking is still valid (not
            // expired, not canceled, etc.) It is important to note here that the opt-in
            // token will not be confirmed if any of the tests below fail.
            $arrRelated = $optInModel->getRelatedRecords();

            if (empty($arrRelated[CalendarEventsMemberModel::getTable()][0])) {
                $this->addCssClassToTemplate('error booking-not-found', $template);

                throw new EventBookingOptInException('Booking not found.', $this->translator->trans('mod_opt_in.error.booking_not_found', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            $booking = CalendarEventsMemberModel::findById($arrRelated[CalendarEventsMemberModel::getTable()][0]);

            if (null === $booking) {
                $this->addCssClassToTemplate('error booking-not-found', $template);

                throw new EventBookingOptInException('Booking not found.', $this->translator->trans('mod_opt_in.error.booking_not_found', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            /** @var CalendarEventsModel $event */
            $event = $booking->getRelated('pid');

            if (null === $event) {
                $this->addCssClassToTemplate('error event-not-found', $template);

                throw new EventBookingOptInException('Event not found.', $this->translator->trans('mod_opt_in.error.corresponding_event_not_found', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            /** @var CalendarModel $calendar */
            $calendar = $event->getRelated('pid');

            if (null === $calendar) {
                $this->addCssClassToTemplate('error calendar-not-found', $template);

                throw new EventBookingOptInException('Calendar not found.', $this->translator->trans('mod_opt_in.error.corresponding_calendar_not_found', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
            }

            if ($this->processConfirm($template, $calendar, $event, $booking, $request)) {
                $request->attributes->set('_calendar_event_booking_token', $booking->bookingToken);

                // Send notification
                if ($calendar->optInSuccessNotification) {
                    $tokens = $this->notificationManager->getNotificationTokens($booking);
                    $this->notificationCenter->sendNotification($calendar->optInSuccessNotification, $tokens);
                }
            }
            $this->connection->commit();
        } catch (OptInTokenAlreadyConfirmedException $e) {
            $message->addInfo($this->translator->trans('mod_opt_in.info.already_confirmed', [], self::TRANS_DOMAIN));
        } catch (OptInTokenNoLongerValidException $e) {
            $message->addInfo($this->translator->trans('mod_opt_in.error.token_no_longer_valid', [], self::TRANS_DOMAIN));
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
        }

        $template->set('messages', $message->hasMessages() ? $message->generate('FE') : null);

        return $template->getResponse();
    }

    private function processConfirm(FragmentTemplate $template, CalendarModel $calendar, CalendarEventsModel $calendarEvent, CalendarEventsMemberModel $booking, Request $request): bool
    {
        $message = $this->getContaoAdapter(Message::class);

        // Check if already canceled
        if ($booking->canceled) {
            $this->addCssClassToTemplate('error booking-canceled', $template);
            $template->set('alreadyCanceled', true);

            throw new EventBookingOptInException('Booking canceled.', $this->translator->trans('mod_opt_in.error.booking_canceled', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
        }

        // Check if already confirmed (optIn === true)
        if ($booking->optIn) {
            $this->addCssClassToTemplate('info already-confirmed', $template);

            $template->set('alreadyConfirmed', true);

            throw new EventBookingOptInException('Booking already confirmed.', $this->translator->trans('mod_opt_in.info.already_confirmed', [], self::TRANS_DOMAIN));
        }

        // Check if opt-in is required
        if (!$calendar->requireOptIn) {
            $this->addCssClassToTemplate('info confirm-not-required', $template);
            $template->set('confirmNotRequired', true);

            throw new EventBookingOptInException('Opt-In not required.', $this->translator->trans('mod_opt_in.info.opt_in_not_required', [], self::TRANS_DOMAIN), SeverityLevel::INFO);
        }

        // Check if booking has been expired
        if ($booking->expired) {
            $this->addCssClassToTemplate('error confirm-expired', $template);
            $template->set('confirmExpired', true);

            throw new EventBookingOptInException('Booking already expired.', $this->translator->trans('mod_opt_in.error.confirm_expired', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
        }

        // Check if past event start date
        if (!empty($calendarEvent->startDate) && time() > $calendarEvent->startDate) {
            $this->addCssClassToTemplate('error confirm-no-more-possible', $template);
            $template->set('cannotConfirm', true);

            throw new EventBookingOptInException('Confirm no more possible.', $this->translator->trans('mod_opt_in.error.confirm_no_more_possible', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
        }

        // Check if past booking date
        if (!empty($calendarEvent->bookingEndDate) && time() > $calendarEvent->bookingEndDate) {
            $this->addCssClassToTemplate('error confirm-no-more-possible', $template);
            $template->set('cannotConfirm', true);

            throw new EventBookingOptInException('Confirm no more possible.', $this->translator->trans('mod_opt_in.error.confirm_no_more_possible', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
        }

        $booking->optIn = true;
        $booking->temporaryReserved = false;
        $booking->save();
        $this->addCssClassToTemplate('confirm-success', $template);
        $template->set('optInSuccess', true);
        $message->addInfo($this->translator->trans('mod_opt_in.info.opt_in_success', [], self::TRANS_DOMAIN));

        $event = new BookingConfirmEvent($booking, self::class, $request);
        $this->eventDispatcher->dispatch($event);

        $this->contaoGeneralLogger?->info(\sprintf('Booking for "%s" ID: %d confirmed via link.', $calendarEvent->title, $booking->id));

        return true;
    }

    private function addCssClassToTemplate(string $cssClass, FragmentTemplate $template): void
    {
        $classes = $template->get('element_css_classes').' '.$cssClass;
        $template->set('element_css_classes', implode(' ', array_filter(explode(' ', $classes))));
    }
}
