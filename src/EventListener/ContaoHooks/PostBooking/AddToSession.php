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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\PostBooking;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\SessionConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook(AddToSession::HOOK, priority: 1200)]
final class AddToSession extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_POST_BOOKING;

    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Add registration to the session.
     *
     * @throws \Exception
     */
    public function __invoke(EventConfig $eventConfig, EventRegistration $eventRegistration): void
    {
        if (!self::isEnabled()) {
            return;
        }

        if (false === $this->connection->fetchOne('SELECT id FROM tl_calendar_events_member WHERE id = ?', [$eventRegistration->getModel()->id])) {
            return;
        }

        $this->addToSession($eventConfig, $eventRegistration);
    }

    /**
     * @throws \Exception
     */
    private function addToSession(EventConfig $eventConfig, EventRegistration $eventRegistration): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $flashBag = $session->getFlashBag();
        $arrSession = [];

        $arrSession['eventData'] = $eventConfig->getModel()->row();
        $arrSession['memberData'] = $eventRegistration->getModel()->row();
        $arrSession['formData'] = $eventRegistration->getForm()->fetchAll();

        $flashBag->set(SessionConfig::FLASH_KEY, $arrSession);
    }
}
