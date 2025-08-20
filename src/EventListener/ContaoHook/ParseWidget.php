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
use Contao\Widget;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingFormController;
use Symfony\Component\HttpFoundation\RequestStack;

final class ParseWidget
{
    public const HOOK = 'parseWidget';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function handleEscorts(string $buffer, Widget $widget): string
    {
        if ('escorts' !== $widget->name) {
            return $buffer;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return $buffer;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $event = $bookingModuleInstance->getEvent();

        // Skip input field "escorts" if escorts are not allowed
        if ($event->maxEscortsPerBooking < 1) {
            return '';
        }

        return $buffer;
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function handleTicketAmount(string $buffer, Widget $widget): string
    {
        if ('ticketAmount' !== $widget->name) {
            return $buffer;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return $buffer;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $event = $bookingModuleInstance->getEvent();

        // Skip input field "escorts" if escorts are not allowed
        if ($event->maxTicketsPerBooking < 2) {
            return '';
        }

        return $buffer;
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function handleWaitingList(string $buffer, Widget $widget): string
    {
        if ('waitingList' !== $widget->name) {
            return $buffer;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return $buffer;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        if (!$bookingModuleInstance->waitingListOpen) {
            return '';
        }

        return $buffer;
    }
}
