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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PostBooking;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\SessionConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber\EventSubscriber;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
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
    public function __invoke(EventConfig $eventConfig, EventSubscriber $eventSubscriber): void
    {
        if (!self::isEnabled()) {
            return;
        }

        if (false === $this->connection->fetchOne('SELECT id FROM tl_calendar_events_member WHERE id = ?', [$eventSubscriber->getModel()->id])) {
            return;
        }

        $this->addToSession($eventConfig, $eventSubscriber);
    }

    private function addToSession(EventConfig $eventConfig, EventSubscriber $eventSubscriber): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $flashBag = $session->getFlashBag();
        $arrSession = [];

        $arrSession['eventData'] = $eventConfig->getModel()->row();
        $arrSession['memberData'] = $eventSubscriber->getModel()->row();
        $arrSession['formData'] = $eventSubscriber->getForm()->fetchAll();

        $flashBag->set(SessionConfig::FLASH_KEY, $arrSession);
    }
}
