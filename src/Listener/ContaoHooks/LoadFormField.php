<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
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
 * Class LoadFormField
 * @package Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks
 */
class LoadFormField
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * LoadFormField constructor.
     * @param ContaoFramework $framework
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param Widget $objWidget
     * @param string $strForm
     * @param array $arrForm
     * @param Form $objForm
     * @return Widget
     */
    public function loadFormField(Widget $objWidget, string $strForm, array $arrForm, Form $objForm): Widget
    {
        if ($objForm->isCalendarEventBookingForm)
        {
            /** @var Date $dateAdapter */
            $dateAdapter = $this->framework->getAdapter(Date::class);

            /** @var CalendarEventsModel $calendarEventsModelAdapter */
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            /** @var Config $configAdapter */
            $configAdapter = $this->framework->getAdapter(Config::class);

            /** @var Input $inputAdapter */
            $inputAdapter = $this->framework->getAdapter(Input::class);

            // Convert tstamps to formated date
            if ($objWidget->name === 'dateOfBirth' && $objWidget->value != '')
            {
                if (is_numeric($objWidget->value))
                {
                    $objWidget->value = $dateAdapter->parse($configAdapter->get('dateFormat'), $objWidget->value);
                    $objWidget->value = $dateAdapter->parse($configAdapter->get('dateFormat'));
                }
            }

            if ($objWidget->name === 'escorts')
            {
                $objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events'));
                if ($objEvent !== null)
                {
                    $maxEscorts = $objEvent->maxEscortsPerMember;
                    if ($maxEscorts > 0)
                    {
                        $opt = [];
                        for ($i = 0; $i <= $maxEscorts; $i++)
                        {
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
