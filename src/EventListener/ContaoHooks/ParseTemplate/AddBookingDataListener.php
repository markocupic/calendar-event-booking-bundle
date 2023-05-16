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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\ParseTemplate;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Template;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventBooking\Template\AddTemplateData;

#[AsHook(AddBookingDataListener::HOOK, priority: 1000)]
class AddBookingDataListener
{
    public const HOOK = 'parseTemplate';

    private Adapter $calendarEventsModelAdapter;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly AddTemplateData $addTemplateData,
        private readonly EventFactory $eventFactory,
    ) {
        $this->calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function __invoke(Template $template): void
    {
        if (str_starts_with($template->getName(), 'event_')) {
            if (empty($template->calendar) || !$template->calendar instanceof CalendarModel) {
                return;
            }

            $event = $this->calendarEventsModelAdapter->findById($template->id);

            if (!$event instanceof CalendarEventsModel) {
                return;
            }

            $eventConfig = $this->eventFactory->create($event);
            $this->addTemplateData->addTemplateData($eventConfig, $template);
        }
    }
}
