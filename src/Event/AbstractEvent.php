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
use Contao\ModuleModel;
use Contao\Template;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractEvent extends Event
{
    protected $disabledSubscribers = [];

    public function getClassProperty(string $key)
    {
        /** @var GenericEvent $event */
        $event = $this->event;

        /** @var CalendarEventBookingEventBookingModuleController $moduleInstance */
        $moduleInstance = $event->getArgument('moduleInstance');

        return $moduleInstance->getProperty($key);
    }

    public function getBookingModuleInstance(): ?CalendarEventBookingEventBookingModuleController
    {
        /** @var GenericEvent $event */
        $event = $this->event;

        /** @var CalendarEventBookingEventBookingModuleController $moduleInstance */
        return $event->getArgument('moduleInstance');
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
