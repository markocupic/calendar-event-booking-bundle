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
use Contao\Widget;
use Markocupic\CalendarEventBookingBundle\Helper\EventUrlResolver;

final class LoadFormField
{
    public const HOOK = 'loadFormField';

    public function __construct(
        private readonly EventUrlResolver $eventUrlResolver,
    ) {
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function handleTicketAmount(Widget $widget, string $strForm, array $arrForm, Form $form): Widget
    {
        if (!$form->isCalendarEventBookingForm) {
            return $widget;
        }

        if ('ticketAmount' !== $widget->name) {
            return $widget;
        }

        $widget->type = 'hidden';

        $event = $this->eventUrlResolver->resolve();

        if (null === $event) {
            return $widget;
        }

        $maxTicketsPerBooking = $event->maxTicketsPerBooking;

        if ($maxTicketsPerBooking < 2) {
            // The input field will be hidden (parseWidget hook)
            $widget->mandatory = false;
            $widget->disabled = true;
        }

        // Add the min & max attribute if the widget type is of type "number"
        $widget->addAttribute('min', 1);
        $widget->addAttribute('max', $maxTicketsPerBooking);
        $widget->addAttribute('step', 1);

        // Add the options if the widget is of type "select"
        if ($maxTicketsPerBooking > 0) {
            $opt = [];

            for ($i = 1; $i <= $maxTicketsPerBooking; ++$i) {
                $opt[] = [
                    'value' => $i,
                    'label' => $i,
                ];
            }
            $widget->options = serialize($opt);
        }

        return $widget;
    }

    #[AsHook(self::HOOK, priority: 1000)]
    public function handleEscorts(Widget $widget, string $strForm, array $arrForm, Form $form): Widget
    {
        if (!$form->isCalendarEventBookingForm) {
            return $widget;
        }

        if ('escorts' !== $widget->name) {
            return $widget;
        }

        $event = $this->eventUrlResolver->resolve();

        if (null === $event) {
            return $widget;
        }

        $maxEscorts = $event->maxEscortsPerBooking;

        if (0 === $maxEscorts) {
            // The input field will be hidden (parseWidget hook)
            $widget->mandatory = false;
        }

        // Add the min & max attribute if the widget type is of type "number"
        $widget->addAttribute('min', 0);
        $widget->addAttribute('max', $maxEscorts);
        $widget->addAttribute('step', 1);

        // Add the options if the widget is of type "select"
        if ($maxEscorts > 0) {
            $opt = [];

            for ($i = 0; $i <= $maxEscorts; ++$i) {
                $opt[] = [
                    'value' => $i,
                    'label' => $i,
                ];
            }
            $widget->options = serialize($opt);
        }

        return $widget;
    }
}
