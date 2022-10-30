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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\PostBooking;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\SessionConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @Hook(AddToSession::HOOK, priority=AddToSession::PRIORITY)
 */
final class AddToSession extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_POST_BOOKING;
    public const PRIORITY = 1200;

    private Connection $connection;
    private RequestStack $requestStack;

    public function __construct(Connection $connection, RequestStack $requestStack)
    {
        $this->connection = $connection;
        $this->requestStack = $requestStack;
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
