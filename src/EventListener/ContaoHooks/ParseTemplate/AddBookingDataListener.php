<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\ParseTemplate;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Template;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventBooking\Template\AddTemplateData;

/**
 * @Hook("parseTemplate")
 */
class AddBookingDataListener
{
    private ContaoFramework $framework;
    private AddTemplateData $addTemplateData;
    private EventFactory $eventFactory;

    public function __construct(ContaoFramework $framework, EventFactory $eventFactory, AddTemplateData $addTemplateData)
    {
        $this->framework = $framework;
        $this->eventFactory = $eventFactory;
        $this->addTemplateData = $addTemplateData;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function __invoke(Template $template): void
    {
        if (0 === strpos($template->getName(), 'event_')) {
            if (empty($template->calendar)) {
                return;
            }

            if (!$template->calendar instanceof CalendarModel) {
                return;
            }

            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);
            $arrData = $template->getData();

            if (null !== ($objEvent = $calendarEventsModelAdapter->findByPk($arrData['id'] ?? null))) {
                $eventConfig = $this->eventFactory->create($objEvent);
                $this->addTemplateData->addTemplateData($eventConfig, $template);
            }
        }
    }
}
