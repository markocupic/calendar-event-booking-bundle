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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PostBooking;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;

/**
 * @Hook(AddToSession::HOOK, priority=AddToSession::PRIORITY)
 */
final class AddToSession
{
    public const HOOK = 'calEvtBookingPostBooking';
    public const PRIORITY = 1200;

    /**
     * @var EventRegistration
     */
    private $eventRegistration;

    public function __construct(EventRegistration $eventRegistration)
    {
        $this->eventRegistration = $eventRegistration;
    }

    /**
     * Add registration to the session.
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance, array $arrDisabledHooks = []): void
    {
        if (\in_array(self::class, $arrDisabledHooks, true)) {
            return;
        }

        $objEvent = $moduleInstance->getProperty('objEvent');
        $objEventMember = $moduleInstance->getProperty('objEventMember');
        $objForm = $moduleInstance->getProperty('objForm');

        $this->eventRegistration->addToSession($objEvent, $objEventMember, $objForm);
    }
}
