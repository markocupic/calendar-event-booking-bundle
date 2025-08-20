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
use Markocupic\CalendarEventBookingBundle\Helper\NotificationManager;
use Markocupic\CalendarEventBookingBundle\Helper\SessionManager;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ProcessFormDataListener
{
    public const HOOK = 'processFormData';

    public function __construct(
        private readonly LoggerInterface|null $contaoGeneralLogger,
        private readonly NotificationManager $notificationManager,
        private readonly RequestStack $requestStack,
        private readonly SessionManager $sessionManager,
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

        $event = $bookingModuleInstance->getEvent();

        $this->sessionManager->addToSession($event, $booking, $formData);
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

        $event = $bookingModuleInstance->getEvent();

        $strText = \sprintf('New event booking for event "%s" and booking token %s.', $event->title, $booking->bookingToken);

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

        if (null === $booking) {
            return;
        }

        // Send the subscribing to the event notification.
        if ($calendar?->subscribeNotification) {
            // Add an extra layer. So we can implement the SendNotificationEvent.
            $this->notificationManager->sendNotification($calendar->subscribeNotification, $this->notificationManager->getNotificationTokens($booking), $booking);
        }

        // Send the opt-in invitation notification.
        if (!$calendar?->requireOptIn || !$calendar?->optInInvitationNotification) {
            return;
        }

        // Add an extra layer. So we can implement the SendNotificationEvent.
        $this->notificationManager->sendNotification($calendar->optInInvitationNotification, $this->notificationManager->getNotificationTokens($booking), $booking);
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

        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        if (!$bookingModuleInstance instanceof EventBookingFormController) {
            return null;
        }

        return $bookingModuleInstance;
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
