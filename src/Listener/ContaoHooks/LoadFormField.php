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

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\Form;
use Contao\Widget;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Contao\CoreBundle\ServiceAnnotation\Hook;

/**
 * @Hook(LoadFormField::HOOK, priority=LoadFormField::PRIORITY)
 */
final class LoadFormField
{
    public const HOOK = 'loadFormField';
    public const PRIORITY = 1000;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var EventRegistration
     */
    private $eventRegistration;

    public function __construct(ContaoFramework $framework, EventRegistration $eventRegistration)
    {
        $this->framework = $framework;
        $this->eventRegistration = $eventRegistration;
    }

    public function __invoke(Widget $objWidget, string $strForm, array $arrForm, Form $objForm): Widget
    {
        if ($objForm->isCalendarEventBookingForm) {
            $dateAdapter = $this->framework->getAdapter(Date::class);
            $configAdapter = $this->framework->getAdapter(Config::class);
            $controllerAdapter = $this->framework->getAdapter(Controller::class);

            // Load DCA
            $controllerAdapter->loadDataContainer('tl_calendar_events_member');
            $dca = $GLOBALS['TL_DCA']['tl_calendar_events_member'];

            // Convert timestamps to formatted date strings
            if (isset($dca['fields'][$objWidget->name]['eval']['rgxp'])) {
                if (!empty($objWidget->value)) {
                    if (is_numeric($objWidget->value)) {
                        if ('date' === $dca['fields'][$objWidget->name]['eval']['rgxp']) {
                            $objWidget->value = $dateAdapter->parse($configAdapter->get('dateFormat'), $objWidget->value);
                        }

                        if ('datim' === $dca['fields'][$objWidget->name]['eval']['rgxp']) {
                            $objWidget->value = $dateAdapter->parse($configAdapter->get('datimFormat'), $objWidget->value);
                        }
                    }
                }
            }

            if ('escorts' === $objWidget->name) {
                $objEvent = $this->eventRegistration->getEventFromCurrentUrl();

                if (null !== $objEvent) {
                    $maxEscorts = $objEvent->maxEscortsPerMember;

                    if ($maxEscorts > 0) {
                        $opt = [];

                        for ($i = 0; $i <= $maxEscorts; ++$i) {
                            $opt[] = [
                                'value' => $i,
                                'label' => $i,
                            ];
                        }
                        $objWidget->options = serialize($opt);
                    }
                }
            }
        }

        return $objWidget;
    }
}
