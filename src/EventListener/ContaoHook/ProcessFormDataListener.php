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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingFormController;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\Session\BookingFlashStorage;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ProcessFormDataListener
{
    public const string HOOK = 'processFormData';

    public function __construct(
        private readonly LoggerInterface|null $contaoGeneralLogger,
        private readonly NotificationService $notificationService,
        private readonly RequestStack $requestStack,
        private readonly BookingFlashStorage $bookingFlashStorage,
    ) {
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function addBookingTokenToFlashBag(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!$this->isValidEventBookingRequest($form)) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->get('_calendar_event_booking_token')) {
            return;
        }

        $flash = $request->getSession()->getFlashBag();

        $flash->add('_calendar_event_booking_token', $request->attributes->get('_calendar_event_booking_token'));
    }

    #[AsHook(self::HOOK, priority: 900)]
    public function addBookingToSession(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!$this->isValidEventBookingRequest($form)) {
            return;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $this->getBookingModuleInstanceFromRequest();

        if (null === $bookingModuleInstance) {
            return;
        }

        $booking = $this->getCurrentBookingFromRequest();

        if (null === $booking) {
            return;
        }

        $calEvent = $bookingModuleInstance->getEvent();

        $this->bookingFlashStorage->addToSession($calEvent, $booking, $formData);
    }

    #[AsHook(self::HOOK, priority: 800)]
    public function contaoLog(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!$this->isValidEventBookingRequest($form)) {
            return;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $this->getBookingModuleInstanceFromRequest();
        if (null === $bookingModuleInstance) {
            return;
        }

        $booking = $this->getCurrentBookingFromRequest();

        if (null === $booking) {
            return;
        }

        $calEvent = $bookingModuleInstance->getEvent();

        $strText = \sprintf('New booking ID %s for event "%s".', $booking->id, $calEvent->title);

        $this->contaoGeneralLogger?->info($strText);
    }

    #[AsHook(self::HOOK, priority: 700)]
    public function sendNotifications(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!$this->isValidEventBookingRequest($form)) {
            return;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $this->getBookingModuleInstanceFromRequest();

        $calendar = $bookingModuleInstance->getCalendar();

        $booking = $this->getCurrentBookingFromRequest();

        if (null === $booking || null === $calendar) {
            return;
        }

        // Send the event-subscribe notification.
        if ($calendar->subscribeNotification) {
            $this->notificationService->sendNotification($calendar->subscribeNotification, $this->notificationService->getNotificationTokens($booking), $booking);
        }

        // Send the opt-in invitation notification.
        if ($calendar->requireOptIn && $calendar->optInInvitationNotification) {
            $this->notificationService->sendNotification($calendar->optInInvitationNotification, $this->notificationService->getNotificationTokens($booking), $booking);
        }
    }

    private function isValidEventBookingRequest(Form $form): bool
    {
        if (!$form->isCalendarEventBookingForm) {
            return false;
        }

        if (null === $this->getBookingModuleInstanceFromRequest()) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return false;
        }

        $booking = $this->getCurrentBookingFromRequest();

        if (null === $booking) {
            return false;
        }

        return true;
    }

    private function getBookingModuleInstanceFromRequest(): EventBookingFormController|null
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        return $request->attributes->get('_event_booking_form_module');
    }

    private function getCurrentBookingFromRequest(): CalendarEventsMemberModel|null
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        $bookingToken = $request->attributes->get('_calendar_event_booking_token', null);

        if (null === $bookingToken) {
            return null;
        }

        return CalendarEventsMemberModel::findOneByBookingToken($bookingToken);
    }
}
