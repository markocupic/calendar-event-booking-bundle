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

namespace Markocupic\CalendarEventBookingBundle\Subscriber\FormatFormData;

use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Event\FormatFormDataEvent;
use Markocupic\CalendarEventBookingBundle\Event\PostBookingEvent;
use Markocupic\CalendarEventBookingBundle\Helper\Formatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FormatFormDataSubscriber implements EventSubscriberInterface
{
    public const PRIORITY = 100;

    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostBookingEvent::NAME => ['formatUserInput', self::PRIORITY],
        ];
    }

    /**
     * Format user input e.g. dates, email addresses,...
     *
     * @throws \Exception
     */
    public function formatUserInput(FormatFormDataEvent $event): void
    {
        $objForm = $event->getForm();
        $objEventMember = $event->getEventMember();
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
