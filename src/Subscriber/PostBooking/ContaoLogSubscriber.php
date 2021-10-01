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

namespace Markocupic\CalendarEventBookingBundle\Subscriber\PostBooking;

use Contao\CalendarEventsModel;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Event\PostBookingEvent;
use Markocupic\CalendarEventBookingBundle\Logger\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ContaoLogSubscriber implements EventSubscriberInterface
{
    public const PRIORITY = 1100;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostBookingEvent::NAME => ['log', self::PRIORITY],
        ];
    }

    /**
     * Contao system log after sending an event booking request.
     */
    public function log(PostBookingEvent $event): void
    {
        if ($event->isDisabled(self::class)) {
            return;
        }

        /** @var CalendarEventBookingEventBookingModuleController $moduleInstance */
        $moduleInstance = $event->getBookingModuleInstance();

        /** @var CalendarEventsModel $objEvent */
        $objEvent =  $moduleInstance->getProperty('objEvent');

        $strText = 'New booking for event with title "'.$objEvent->title.'"';
        $level = LogLevel::INFO;
        $this->logger->log($strText, $level);
    }
}
