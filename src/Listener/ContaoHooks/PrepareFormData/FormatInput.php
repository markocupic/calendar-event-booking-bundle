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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PrepareFormData;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber\EventSubscriber;
use Markocupic\CalendarEventBookingBundle\EventBooking\Utils\Formatter;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

/**
 * @Hook(FormatInput::HOOK, priority=FormatInput::PRIORITY)
 */
final class FormatInput extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_PREPARE_FORM_DATA;
    public const PRIORITY = 1000;

    private Formatter $formatter;
    private EventSubscriber $eventSubscriber;

    public function __construct(Formatter $formatter, EventSubscriber $eventSubscriber)
    {
        $this->formatter = $formatter;
        $this->eventSubscriber = $eventSubscriber;
    }

    /**
     * Format user input e.g. dates, email addresses,...
     *
     * @throws \Exception
     */
    public function __invoke(Form $form, EventConfig $eventConfig, CalendarEventsMemberModel $eventMember): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $strTable = $this->eventSubscriber->getTable();

        foreach (array_keys($form->getFormFields()) as $strFieldname) {
            $varValue = $eventMember->$strFieldname;
            $varValue = $this->formatter->convertDateFormatsToTimestamps($varValue, $strTable, $strFieldname);
            $varValue = $this->formatter->formatEmail($varValue, $strTable, $strFieldname);
            $varValue = $this->formatter->getCorrectEmptyValue($varValue, $strTable, $strFieldname);
            $eventMember->$strFieldname = $varValue;
            $eventMember->save();
        }
    }
}
