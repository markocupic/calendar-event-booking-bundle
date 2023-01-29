<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\PrepareFormData;

use Codefog\HasteBundle\Form\Form;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventBooking\Utils\Formatter;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

#[AsHook(FormatInput::HOOK, priority: 1000)]
final class FormatInput extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_PREPARE_FORM_DATA;

    public function __construct(
        private readonly Formatter $formatter,
        private readonly EventRegistration $eventRegistration,
    ) {
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

        $strTable = $this->eventRegistration->getTable();

        foreach (array_keys($form->getFormFields()) as $strFieldName) {
            $varValue = $eventMember->$strFieldName;
            $varValue = $this->formatter->convertDateFormatsToTimestamps($varValue, $strTable, $strFieldName);
            $varValue = $this->formatter->formatEmail($varValue, $strTable, $strFieldName);
            $varValue = $this->formatter->getCorrectEmptyValue($varValue, $strTable, $strFieldName);
            $eventMember->$strFieldName = $varValue;
            $eventMember->save();
        }
    }
}
