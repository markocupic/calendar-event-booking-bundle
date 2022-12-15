<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PrepareFormData;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Helper\Formatter;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

/**
 * @Hook(FormatInput::HOOK, priority=FormatInput::PRIORITY)
 */
final class FormatInput
{
    public const HOOK = 'calEvtBookingPrepareFormData';
    public const PRIORITY = 1000;

    private Formatter $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Format user input e.g. dates, email addresses,...
     *
     * @throws \Exception
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance, array $arrDisabledHooks = []): void
    {
        if (\in_array(self::class, $arrDisabledHooks, true)) {
            return;
        }

        /** @var CalendarEventsMemberModel $objEventMember */
        $objEventMember = $moduleInstance->getProperty('objEventMember');

        /** @var Form $objForm */
        $objForm = $moduleInstance->getProperty('objForm');

        $strTable = CalendarEventBookingEventBookingModuleController::EVENT_SUBSCRIPTION_TABLE;

        foreach (array_keys($objForm->getFormFields()) as $strFieldname) {
            $varValue = $objEventMember->$strFieldname;
            $varValue = $this->formatter->convertDateFormatsToTimestamps($varValue, $strTable, $strFieldname);
            $varValue = $this->formatter->formatEmail($varValue, $strTable, $strFieldname);
            $varValue = $this->formatter->getCorrectEmptyValue($varValue, $strTable, $strFieldname);
            $objEventMember->$strFieldname = $varValue;
            $objEventMember->save();
        }
    }
}
