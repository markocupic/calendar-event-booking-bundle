<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Helper\AddTemplateData;

#[AsHook(ParseTemplate::HOOK, priority: 1000)]
final class ParseTemplate
{
    public const HOOK = 'parseTemplate';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly AddTemplateData $addTemplateData,
    ) {
    }

    /**
     * Add registration data to calendar templates.
     */
    public function __invoke(Template $template): void
    {
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (!str_starts_with($template->getName(), 'event') && !str_starts_with($template->getName(), 'mod_calendar_event_booking')) {
            return;
        }

        $event = null;

        if (str_starts_with($template->getName(), 'mod_calendar_event_booking')) {
            $event = $template->event;
        } elseif (str_starts_with($template->getName(), 'event')) {
            $event = $calendarEventsModelAdapter->findById($template->id);
        }

        if (!$event instanceof CalendarEventsModel) {
            return;
        }

        $this->addTemplateData->addTemplateData($template, $event);
    }
}
