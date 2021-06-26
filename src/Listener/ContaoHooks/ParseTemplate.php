<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Helper\AddTemplateData;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;

/**
 * @Hook("parseTemplate")
 */
class ParseTemplate
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var AddTemplateData
     */
    private $addTemplateData;

    public function __construct(ContaoFramework $framework, AddTemplateData $addTemplateData)
    {
        $this->framework = $framework;
        $this->addTemplateData = $addTemplateData;
    }

    public function __invoke(Template $template): void
    {
        /** @var CalendarEventsModel $calendarEventsModelAdapter */
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (empty($template->calendar) || 0 !== strpos($template->getName(), 'event')) {
            return;
        }

        if (!$template->calendar instanceof CalendarModel) {
            return;
        }

        $event = $calendarEventsModelAdapter->findById($template->id);

        if (null === $event) {
            return;
        }

        $this->addTemplateData->addTemplateData($template, $event);
    }
}
