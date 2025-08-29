<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Markocupic\CalendarEventBookingBundle\CheckoutHandler\CheckoutHandlerAwareTrait;
use Markocupic\CalendarEventBookingBundle\NotificationType\EventBookingNotificationType;
use Markocupic\CalendarEventBookingBundle\NotificationType\EventBookingOptInInvitationNotificationType;
use Markocupic\CalendarEventBookingBundle\NotificationType\EventBookingOptInSuccessNotificationType;
use Markocupic\CalendarEventBookingBundle\NotificationType\EventBookingPaymentSuccessNotificationType;
use Markocupic\CalendarEventBookingBundle\NotificationType\EventUnsubscribeNotificationType;
use Markocupic\CalendarEventBookingBundle\NotificationType\WaitingListAdvancementNotificationType;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

class Calendar
{
    use CheckoutHandlerAwareTrait;

    public function __construct(
        private readonly Connection $connection,
        #[AutowireLocator('cebb.checkout_handler', defaultIndexMethod: 'getType')]
        private readonly ContainerInterface $checkoutHandlers,
    ) {
    }

    #[AsCallback(table: 'tl_calendar', target: 'fields.eventBookingCheckoutHandler.options')]
    public function getCheckoutHandlerTypes(): array
    {
        return $this->getTypes($this->checkoutHandlers);
    }

    #[AsCallback(table: 'tl_calendar', target: 'fields.subscribeNotification.options')]
    public function getEventSubscribeNotifications(): array
    {
        return $this->connection->fetchAllKeyValue(
            'SELECT id,title FROM tl_nc_notification WHERE type = ? ORDER BY title',
            [EventBookingNotificationType::NAME],
            [Types::STRING],
        );
    }

    #[AsCallback(table: 'tl_calendar', target: 'fields.paymentSuccessNotification.options')]
    public function getPaymentSuccessNotifications(): array
    {
        return $this->connection->fetchAllKeyValue(
            'SELECT id,title FROM tl_nc_notification WHERE type = ? ORDER BY title',
            [EventBookingPaymentSuccessNotificationType::NAME],
            [Types::STRING],
        );
    }

    #[AsCallback(table: 'tl_calendar', target: 'fields.waitingListAdvancementNotification.options')]
    public function getWaitingListAdvancementNotifications(): array
    {
        return $this->connection->fetchAllKeyValue(
            'SELECT id,title FROM tl_nc_notification WHERE type = ? ORDER BY title',
            [WaitingListAdvancementNotificationType::NAME],
            [Types::STRING],
        );
    }

    #[AsCallback(table: 'tl_calendar', target: 'fields.unsubscribeNotification.options')]
    public function getUnsubscribeNotifications(): array
    {
        return $this->connection->fetchAllKeyValue(
            'SELECT id,title FROM tl_nc_notification WHERE type = ? ORDER BY title',
            [EventUnsubscribeNotificationType::NAME],
            [Types::STRING],
        );
    }

    #[AsCallback(table: 'tl_calendar', target: 'fields.optInInvitationNotification.options')]
    public function getOptInInvitationNotifications(): array
    {
        return $this->connection->fetchAllKeyValue(
            'SELECT id,title FROM tl_nc_notification WHERE type = ? ORDER BY title',
            [EventBookingOptInInvitationNotificationType::NAME],
            [Types::STRING],
        );
    }

    #[AsCallback(table: 'tl_calendar', target: 'fields.optInSuccessNotification.options')]
    public function getOptInSuccessNotifications(): array
    {
        return $this->connection->fetchAllKeyValue(
            'SELECT id,title FROM tl_nc_notification WHERE type = ? ORDER BY title',
            [EventBookingOptInSuccessNotificationType::NAME],
            [Types::STRING],
        );
    }
}
