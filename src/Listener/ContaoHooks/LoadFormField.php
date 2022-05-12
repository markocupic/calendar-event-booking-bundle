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

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Date;
use Contao\Form;
use Contao\Widget;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber\EventSubscriber;
use Markocupic\CalendarEventBookingBundle\EventBooking\Helper\EventRegistration;

/**
 * @Hook(LoadFormField::HOOK, priority=LoadFormField::PRIORITY)
 */
final class LoadFormField extends AbstractHook
{
    public const HOOK = 'loadFormField';
    public const PRIORITY = 1000;

    private ContaoFramework $framework;
    private EventRegistration $eventRegistration;
    private EventFactory $eventFactory;
    private EventSubscriber $eventSubscriber;

    public function __construct(ContaoFramework $framework, EventRegistration $eventRegistration, EventFactory $eventFactory, EventSubscriber $eventSubscriber)
    {
        $this->framework = $framework;
        $this->eventRegistration = $eventRegistration;
        $this->eventFactory = $eventFactory;
        $this->eventSubscriber = $eventSubscriber;
    }

    public function __invoke(Widget $objWidget, string $strForm, array $arrForm, Form $objForm): Widget
    {
        if (!self::isEnabled()) {
            return $objWidget;
        }

        if ($objForm->isCalendarEventBookingForm) {
            $dateAdapter = $this->framework->getAdapter(Date::class);
            $configAdapter = $this->framework->getAdapter(Config::class);
            $controllerAdapter = $this->framework->getAdapter(Controller::class);

            // Load DCA
            $controllerAdapter->loadDataContainer($this->eventSubscriber->getTable());
            $dca = $GLOBALS['TL_DCA'][$this->eventSubscriber->getTable()];

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

            // Fit select menu to max escorts per member
            if ('escorts' === $objWidget->name) {
                /** @var CalendarEventsModel $objEvent */
                $objEvent = $this->eventRegistration->getEventFromCurrentUrl();

                $eventConfig = $this->eventFactory->create($objEvent);

                $maxEscorts = $eventConfig->get('maxEscortsPerMember');

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

        return $objWidget;
    }
}
