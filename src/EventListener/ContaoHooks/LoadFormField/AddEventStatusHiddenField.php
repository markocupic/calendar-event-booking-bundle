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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\LoadFormField;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;

#[AsHook(AddEventStatusHiddenField::HOOK, priority: 1000)]
final class AddEventStatusHiddenField extends AbstractHook
{
    public const HOOK = 'loadFormField';

    public function __construct(
        private readonly EventFactory $eventFactory,
    ) {
    }

    public function __invoke(Widget $objWidget, string $formId, array $arrForm, Form $objForm): Widget
    {
        if (!self::isEnabled()) {
            return $objWidget;
        }

        if ($objForm->isCalendarEventBookingForm) {
            if (null === ($event = EventConfig::getEventFromRequest())) {
                return $objWidget;
            }

            $eventConfig = $this->eventFactory->create($event);

            if (!$eventConfig->get('enableBookingForm') || !$eventConfig->get('published')) {
                return $objWidget;
            }

            // Add the event status to the hidden field used for terminal42/contao-conditionalformfields.
            // The hidden field has to be added manually if the autogenerated form is not used.
            if ('eventStatus' === $objWidget->name) {
                $objWidget->value = $eventConfig->getEventStatus();
            }
        }

        return $objWidget;
    }
}
