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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingFormController;
use Markocupic\CalendarEventBookingBundle\Helper\EventBooking;
use Markocupic\CalendarEventBookingBundle\Helper\NotificationHelper;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class ProcessFormDataListener
{
    public const HOOK = 'processFormData';

    public function __construct(
        private readonly EventBooking $eventBooking,
        private readonly NotificationCenter $notificationCenter,
        private readonly NotificationHelper $notificationHelper,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface|null $contaoGeneralLogger,
    ) {
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function addBookingTokenToFlashBag(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!$form->isCalendarEventBookingForm) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_calendar_event_booking_token')) {
            return;
        }

        $flash = $request->getSession()->getFlashBag();

        $flash->add('_calendar_event_booking_token', $request->attributes->get('_calendar_event_booking_token'));
    }

    #[AsHook(self::HOOK, priority: 900)]
    public function addBookingToSession(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!$form->isCalendarEventBookingForm) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return;
        }

        $bookingToken = $request->attributes->get('_calendar_event_booking_token');

        $booking = CalendarEventsMemberModel::findOneByBookingToken($bookingToken);

        if (null === $booking) {
            return;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $event = $bookingModuleInstance->getEvent();

        $this->eventBooking->addToSession($event, $booking, $request);
    }

    #[AsHook(self::HOOK, priority: 800)]
    public function contaoLog(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!$form->isCalendarEventBookingForm) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return;
        }

        $bookingToken = $request->attributes->get('_calendar_event_booking_token');

        $booking = CalendarEventsMemberModel::findOneByBookingToken($bookingToken);

        if (null === $booking) {
            return;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $event = $bookingModuleInstance->getEvent();

        $strText = "New event booking for event '$event->title' and booking token $booking->bookingToken.";

        $this->contaoGeneralLogger?->info($strText);
    }

    #[AsHook(self::HOOK, priority: 700)]
    public function sendNotification(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!$form->isCalendarEventBookingForm) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return;
        }

        $bookingToken = $request->attributes->get('_calendar_event_booking_token');

        $booking = CalendarEventsMemberModel::findOneByBookingToken($bookingToken);

        if (null === $booking) {
            return;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $calendar = $bookingModuleInstance->getCalendar();

        if (!$calendar?->subscribeNotification) {
            return;
        }

        // Add an extra layer. So we can implement the SendNotificationEvent.
        $this->notificationHelper->sendNotification($calendar->subscribeNotification, $this->notificationHelper->getNotificationTokens($booking), $booking);
    }
}
