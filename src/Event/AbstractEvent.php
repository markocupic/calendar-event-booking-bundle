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

namespace Markocupic\CalendarEventBookingBundle\Event;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\FormModel;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractEvent extends Event
{
    protected $disabledSubscribers = [];

    public function getEvent(): CalendarEventsModel
    {
        return $this->event->getArgument('objEvent');
    }

    public function getEventMember(): CalendarEventsMemberModel
    {
        return $this->event->getArgument('objEventMember');
    }

    public function getForm(): Form
    {
        return $this->event->getArgument('objForm');
    }

    public function getFormGeneratorModel(): FormModel
    {
        return $this->event->getArgument('objFormGeneratorModel');
    }

    public function getModuleInstance(): AbstractController
    {
        return $this->event->getArgument('objEvent');
    }

    public function disableSubscriber(string $strSubscriber): void
    {
        $this->disabledSubscribers[] = $strSubscriber;
        $this->disabledSubscribers = array_unique($this->disabledSubscribers);
    }

    public function isDisabled($strSubscriber): bool
    {
        if (\in_array($strSubscriber, $this->disabledSubscribers, true)) {
            return true;
        }

        return false;
    }

    public function getDisabledSubscribers(): array
    {
        return $this->disabledSubscribers;
    }
}
