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

use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Helper\AddTemplateData;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook(ParseTemplate::HOOK, priority: 1000)]
final class ParseTemplate
{
    public const HOOK = 'parseTemplate';

    public function __construct(
        private readonly AddTemplateData $addTemplateData,
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Add booking data to legacy calendar templates.
     */
    public function __invoke(Template $template): void
    {
        $this->framework->initialize();

        if (!str_starts_with($template->getName(), 'event')) {
            return;
        }

        $event = $this->framework->getAdapter(CalendarEventsModel::class)->findById($template->id ?? 0);

        if (null === $event) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        foreach ($this->addTemplateData->getData($event, $request) as $key => $value) {
            $template->{$key} = $value;
        }
    }
}
