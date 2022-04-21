<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
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

/**
 * @Hook(ParseTemplate::HOOK, priority=ParseTemplate::PRIORITY)
 */
final class ParseTemplate
{
    public const HOOK = 'parseTemplate';
    public const PRIORITY = 1000;

    private static bool $disableHook = false;

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

    /**
     * Add registration data to calendar templates.
     */
    public function __invoke(Template $template): void
    {
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

    public static function disableHook(): void
    {
        self::$disableHook = true;
    }

    public static function enableHook(): void
    {
        self::$disableHook = false;
    }

    public static function isEnabled(): bool
    {
        return self::$disableHook;
    }
}
