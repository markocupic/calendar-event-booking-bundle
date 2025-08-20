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
use Contao\Validator;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingFormController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidateFormFieldListener
{
    public const HOOK = 'validateFormField';

    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function validateEmail(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (!$form->isCalendarEventBookingForm) {
            return $widget;
        }

        if ('email' !== $widget->name) {
            return $widget;
        }

        if (empty($widget->value)) {
            return $widget;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return $widget;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $widget->value = strtolower($widget->value);

        $calendar = $bookingModuleInstance->getCalendar();

        if ($calendar->emailUnique) {
            return $widget;
        }

        $event = $bookingModuleInstance->getEvent();

        $count = $this->connection->fetchOne(
            'SELECT COUNT(id) FROM tl_calendar_events_member WHERE pid = ? AND email LIKE ?',
            [$event->id, $widget->value],
        );

        // Check if user with submitted email has already booked
        if ($count) {
            $errorMsg = $this->translator->trans('ERR.duplicate_email', [$widget->value], 'contao_default');
            $widget->addError($errorMsg);
        }

        return $widget;
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function validateEscorts(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (!$form->isCalendarEventBookingForm) {
            return $widget;
        }

        if ('escorts' !== $widget->name) {
            return $widget;
        }

        if (empty($widget->value)) {
            $widget->value = 0;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return $widget;
        }

        if (!Validator::isNatural($widget->value) || $widget->value < 0) {
            $errorMsg = $this->translator->trans('ERR.enter_positive_integer', [], 'contao_default');
            $widget->addError($errorMsg);

            return $widget;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $event = $bookingModuleInstance->getEvent();

        if (empty($widget->value) || $widget->value < 1) {
            $widget->value = 0;
        }

        if ((int) $widget->value > (int) $event->maxEscortsPerBooking) {
            $errorMsg = $this->translator->trans('ERR.max_escorts_per_booking', [$event->maxEscortsPerBooking], 'contao_default');
            $widget->addError($errorMsg);
        }

        return $widget;
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function validateTicketAmount(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (!$form->isCalendarEventBookingForm) {
            return $widget;
        }

        if ('ticketAmount' !== $widget->name) {
            return $widget;
        }

        if (empty($widget->value)) {
            $widget->value = 1;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return $widget;
        }

        if (!Validator::isNatural($widget->value) || $widget->value < 0) {
            $errorMsg = $this->translator->trans('ERR.enter_positive_integer', [], 'contao_default');
            $widget->addError($errorMsg);

            return $widget;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $event = $bookingModuleInstance->getEvent();

        if (empty($widget->value) || $widget->value < 1) {
            $widget->value = 0;
        }

        if ((int) $widget->value > (int) $event->maxTicketsPerBooking) {
            $errorMsg = $this->translator->trans('ERR.max_tickets_per_booking', [$event->maxTicketsPerBooking], 'contao_default');
            $widget->addError($errorMsg);
        }

        return $widget;
    }
}
