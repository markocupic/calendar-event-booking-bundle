<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\Form;
use Contao\Widget;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;

#[AsHook(LoadFormField::HOOK, priority: 1000)]
final class LoadFormField
{
    public const HOOK = 'loadFormField';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly EventRegistration $eventRegistration,
    ) {
    }

    public function __invoke(Widget $widget, string $strForm, array $arrForm, Form $form): Widget
    {
        if ($form->isCalendarEventBookingForm) {
            $dateAdapter = $this->framework->getAdapter(Date::class);
            $configAdapter = $this->framework->getAdapter(Config::class);
            $controllerAdapter = $this->framework->getAdapter(Controller::class);

            // Load DCA
            $controllerAdapter->loadDataContainer('tl_calendar_events_member');
            $dca = $GLOBALS['TL_DCA']['tl_calendar_events_member'];

            // Convert timestamps to formatted date strings
            if (isset($dca['fields'][$widget->name]['eval']['rgxp'])) {
                if (!empty($widget->value)) {
                    if (is_numeric($widget->value)) {
                        if ('date' === $dca['fields'][$widget->name]['eval']['rgxp']) {
                            $widget->value = $dateAdapter->parse($configAdapter->get('dateFormat'), $widget->value);
                        }

                        if ('datim' === $dca['fields'][$widget->name]['eval']['rgxp']) {
                            $widget->value = $dateAdapter->parse($configAdapter->get('datimFormat'), $widget->value);
                        }
                    }
                }
            }

            if ('escorts' === $widget->name) {
                $event = $this->eventRegistration->getEventFromCurrentUrl();

                if (null !== $event) {
                    $maxEscorts = $event->maxEscortsPerMember;

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
                }
            }
        }

        return $widget;
    }
}
