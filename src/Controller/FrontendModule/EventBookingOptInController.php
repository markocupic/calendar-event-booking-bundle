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
use Contao\CoreBundle\OptIn\OptInTokenAlreadyConfirmedException;
use Contao\CoreBundle\OptIn\OptInTokenNoLongerValidException;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\OptInModel;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Markocupic\CalendarEventBookingBundle\Event\BookingConfirmEvent;
use Markocupic\CalendarEventBookingBundle\Exception\AbstractTranslatableException;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingOptInException;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\OptIn\OptInTokenFactory;
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

#[AsFrontendModule(EventBookingOptInController::TYPE, category: 'events')]
class EventBookingOptInController extends AbstractFrontendModuleController
{
    public const string TYPE = 'event_booking_opt_in';

    public const string ACTION = 'opt-in';

    private const string TRANS_DOMAIN = 'mc_calendar_event_booking';

    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly FigureUtil $figureUtil,
        private readonly LockFactory $lockFactory,
        #[Autowire(service: 'markocupic_calendar_event_booking.flash_message.opt_in')]
        private readonly MessageInterface $message,
        private readonly NotificationCenter $notificationCenter,
        private readonly NotificationService $notificationService,
        private readonly TranslatorInterface $translator,
        private readonly OptInTokenFactory $optInTokenFactory,
        private readonly LoggerInterface|null $contaoErrorLogger,
        private readonly LoggerInterface|null $contaoGeneralLogger,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $token = $request->query->get('token');
        $action = $request->query->get('action');

        if (self::ACTION !== $action || empty($token)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $optInModel = $this->getContaoAdapter(OptInModel::class)->findOneByToken($token);

        if (null === $optInModel) {
            $this->addCssClassToTemplate('error confirm-no-more-possible', $template);
            $this->message->addError($this->translator->trans('mod_opt_in.error.confirm_no_more_possible', [], self::TRANS_DOMAIN));

            $template->set('messagesUnwrapped', $this->message->renderUnwrapped(peek: true));
            $template->set('messages', $this->message->hasMessages() ? $this->message->getAll() : null);

            return $template->getResponse();
        }

        $booking = $this->loadBookingFromOptInModel($optInModel);
        $calEvent = $booking?->getRelated('pid');

        $this->processOptInConfirmation($template, $optInModel, $token, $request, $booking, $calEvent);

        // Add image to template
        $this->addEventImageToTemplate($template, $model, $calEvent);

        // Peek messages and wrap them in a <p> tag.
        $template->set('messagesUnwrapped', $this->message->renderUnwrapped(peek: true));
        $template->set('messages', $this->message->hasMessages() ? $this->message->getAll() : null);

        return $template->getResponse();
    }

    private function processOptInConfirmation(FragmentTemplate $template, OptInModel $optInModel, string $token, Request $request, CalendarEventsMemberModel|null $booking, CalendarEventsModel|null $calEvent): void
    {
        /** @var CalendarModel|null $calendar */
        $calendar = $calEvent?->getRelated('pid');

        $lock = $this->lockFactory->createLock(base64_encode(self::class.$token));
        $lock->acquire(true);

        try {
            $this->connection->beginTransaction();

            $this->validateRelatedEntities($template, $booking, $calEvent, $calendar);

            $request->attributes->set('_calendar_event_booking_token', $booking->bookingToken);

            $this->validateBookingState($template, $calendar, $calEvent, $booking);

            $optInToken = $this->optInTokenFactory->create($optInModel);

            // Will throw an exception if the token is already confirmed or no longer valid.
            $optInToken->confirm();

            $this->processConfirm($template, $calEvent, $booking, $request);

            $this->sendOptInSuccessNotification($calendar, $booking);

            $this->connection->commit();
        } catch (OptInTokenAlreadyConfirmedException $e) {
            $this->handleTransactionRollback();
            $this->message->addInfo($this->translator->trans('mod_opt_in.info.already_confirmed', [], self::TRANS_DOMAIN));
        } catch (OptInTokenNoLongerValidException $e) {
            $this->handleTransactionRollback();
            $this->message->addInfo($this->translator->trans('mod_opt_in.error.token_no_longer_valid', [], self::TRANS_DOMAIN));
        } catch (AbstractTranslatableException $e) {
            $this->handleTransactionRollback();
            $this->message->add($this->translator->trans($e->getMessageKey(), $e->getMessageData(), $e->getMessageDomain()), $e->getSeverityLevel());
        } catch (\Throwable $e) {
            $this->handleTransactionRollback();
            $this->message->addError($this->translator->trans('mod_opt_in.error.unexpected_error', [], self::TRANS_DOMAIN));
            $this->contaoErrorLogger?->error($e->getMessage());
        } finally {
            $lock->release();
        }
    }

    private function handleTransactionRollback(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }
    }

    private function addEventImageToTemplate(FragmentTemplate $template, ModuleModel $model, CalendarEventsModel|null $calEvent): void
    {
        if (null === $calEvent || !$model->ceb_addImage || !$calEvent->addImage) {
            return;
        }

        $figure = $this->figureUtil->buildFigure($calEvent->row());

        if (null !== $figure) {
            $template->set('addImage', true);
            $template->set('figure', $figure);
        }
    }

    private function processConfirm(FragmentTemplate $template, CalendarEventsModel $calEvent, CalendarEventsMemberModel $booking, Request $request): void
    {
        $booking->optIn = true;
        $booking->temporaryReserved = false;
        $booking->save();

        $this->addCssClassToTemplate('confirm-success', $template);
        $template->set('optInSuccess', true);
        $this->message->addInfo($this->translator->trans('mod_opt_in.info.opt_in_success', [], self::TRANS_DOMAIN));

        $event = new BookingConfirmEvent($booking, self::class, $request);
        $this->eventDispatcher->dispatch($event);

        $this->contaoGeneralLogger?->info(\sprintf('Booking for "%s" ID: %d confirmed via link.', $calEvent->title, $booking->id));
    }

    private function validateRelatedEntities(FragmentTemplate $template, CalendarEventsMemberModel|null $booking, CalendarEventsModel|null $calEvent, CalendarModel|null $calendar): void
    {
        if (null === $booking) {
            $this->addCssClassToTemplate('error booking-not-found', $template);

            throw new EventBookingOptInException('Booking not found.', SeverityLevel::ERROR, 'mod_opt_in.error.booking_not_found', [], self::TRANS_DOMAIN);
        }

        if (null === $calEvent) {
            $this->addCssClassToTemplate('error event-not-found', $template);

            throw new EventBookingOptInException('Event not found.', SeverityLevel::ERROR, 'mod_opt_in.error.corresponding_event_not_found', [], self::TRANS_DOMAIN);
        }

        if (null === $calendar) {
            $this->addCssClassToTemplate('error calendar-not-found', $template);

            throw new EventBookingOptInException('Calendar not found.', SeverityLevel::ERROR, 'mod_opt_in.error.corresponding_calendar_not_found', [], self::TRANS_DOMAIN);
        }
    }

    private function validateBookingState(FragmentTemplate $template, CalendarModel $calendar, CalendarEventsModel $calEvent, CalendarEventsMemberModel $booking): void
    {
        if (!$calendar->allowEventBooking || !$calEvent->enableBookingForm) {
            $this->throwValidationException($template, 'error event-not-bookable', 'eventNotBookable', SeverityLevel::ERROR, 'mod_opt_in.error.event_not_bookable', 'Event is not bookable.');
        }

        if ($booking->canceled) {
            $this->throwValidationException($template, 'error booking-canceled', 'alreadyCanceled', SeverityLevel::ERROR, 'mod_opt_in.error.booking_canceled', 'Booking canceled.');
        }

        if ($booking->optIn) {
            $this->throwValidationException($template, 'info already-confirmed', 'alreadyConfirmed', SeverityLevel::INFO, 'mod_opt_in.info.already_confirmed', 'Booking already confirmed.');
        }

        if (!$calendar->requireOptIn) {
            $this->throwValidationException($template, 'info confirm-not-required', 'confirmNotRequired', SeverityLevel::INFO, 'mod_opt_in.info.opt_in_not_required', 'Opt-In not required.');
        }

        if ($booking->expired) {
            $this->throwValidationException($template, 'error confirm-expired', 'confirmExpired', SeverityLevel::ERROR, 'mod_opt_in.error.confirm_expired', 'Booking already expired.');
        }

        if (!empty($calEvent->startDate) && time() > $calEvent->startDate) {
            $this->throwValidationException($template, 'error confirm-no-more-possible', 'cannotConfirm', SeverityLevel::ERROR, 'mod_opt_in.error.confirm_no_more_possible', 'Confirm no more possible.');
        }

        if (!empty($calEvent->bookingEndDate) && time() > $calEvent->bookingEndDate) {
            $this->throwValidationException($template, 'error confirm-no-more-possible', 'cannotConfirm', SeverityLevel::ERROR, 'mod_opt_in.error.confirm_no_more_possible', 'Confirm no more possible.');
        }
    }

    private function throwValidationException(FragmentTemplate $template, string $cssClass, string $templateKey, SeverityLevel $severity, string $transKey, string $message): never
    {
        $this->addCssClassToTemplate($cssClass, $template);
        $template->set($templateKey, true);

        throw new EventBookingOptInException($message, $severity, $transKey, [], self::TRANS_DOMAIN);
    }

    private function addCssClassToTemplate(string $cssClass, FragmentTemplate $template): void
    {
        $classes = $template->get('element_css_classes').' '.$cssClass;
        $template->set('element_css_classes', implode(' ', array_filter(explode(' ', $classes))));
    }

    private function loadBookingFromOptInModel(OptInModel $optInModel): CalendarEventsMemberModel|null
    {
        $arrRelated = $optInModel->getRelatedRecords();

        if (empty($arrRelated[CalendarEventsMemberModel::getTable()][0])) {
            return null;
        }

        return $this->getContaoAdapter(CalendarEventsMemberModel::class)->findById($arrRelated[CalendarEventsMemberModel::getTable()][0]);
    }

    private function sendOptInSuccessNotification(CalendarModel $calendar, CalendarEventsMemberModel $booking): void
    {
        if ($calendar->optInSuccessNotification) {
            $tokens = $this->notificationService->getNotificationTokens($booking);
            $this->notificationCenter->sendNotification($calendar->optInSuccessNotification, $tokens);
        }
    }
}
