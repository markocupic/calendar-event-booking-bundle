<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2020 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\Form;
use Contao\Input;
use Contao\Widget;

/**
 * Class LoadFormField.
 */
class LoadFormField
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * LoadFormField constructor.
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function loadFormField(Widget $objWidget, string $strForm, array $arrForm, Form $objForm): Widget
    {
        if ($objForm->isCalendarEventBookingForm) {
            /** @var Date $dateAdapter */
            $dateAdapter = $this->framework->getAdapter(Date::class);

            /** @var CalendarEventsModel $calendarEventsModelAdapter */
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            /** @var Config $configAdapter */
            $configAdapter = $this->framework->getAdapter(Config::class);

            /** @var Input $inputAdapter */
            $inputAdapter = $this->framework->getAdapter(Input::class);

            // Convert tstamps to formated date
            if ('dateOfBirth' === $objWidget->name && '' !== $objWidget->value) {
                if (is_numeric($objWidget->value)) {
                    $objWidget->value = $dateAdapter->parse($configAdapter->get('dateFormat'), $objWidget->value);
                    $objWidget->value = $dateAdapter->parse($configAdapter->get('dateFormat'));
                }
            }

            if ('escorts' === $objWidget->name) {
                $objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events'));

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
