<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Event\WaitingListAdvancementEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Terminal42\NotificationCenterBundle\NotificationCenter;

/**
 * This class is used to fill free spots with bookings on the waiting list. Early
 * bookings are prioritised.
 */
class WaitingListManager
{
    private array $processedIds = [0];

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventBooking $eventBooking,
        private readonly LockFactory $lockFactory,
        private readonly NotificationCenter $notificationCenter,
        private readonly NotificationHelper $notificationHelper,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface|null $contaoGeneralLogger,
    ) {
    }

    public function checkWaitingList(CalendarEventsModel|null $event = null): void
    {
        $this->framework->initialize();

        $lock = $this->lockFactory->createLock(self::class);
        $lock->acquire(true);

        try {
            $events = $this->getEventsToProcess($event);

            if (null === $events) {
                return;
            }

            while ($events->next()) {
                $currentEvent = $events->current();

                if (!$currentEvent->enableWaitingList) {
                    continue;
                }

                $this->processWaitingListForEvent($currentEvent);
            }
        } finally {
            $lock->release();
        }
    }

    private function getEventsToProcess(CalendarEventsModel|null $event): Collection|null
    {
        if ($event) {
            return new Collection([$event], CalendarEventsMemberModel::getTable());
        }

        $calendarIds = CalendarModel::findAll()?->fetchEach('id') ?? [];

        return CalendarEventsModel::findUpcomingByPids($calendarIds);
    }

    private function processWaitingListForEvent(CalendarEventsModel $event): void
    {
        while (($availableSlots = $event->maxBookings - $this->eventBooking->getBookingCount($event)) > 0) {
            $nextBooking = $this->findNextEligibleBooking($event, $availableSlots);

            if (null === $nextBooking) {
                break;
            }

            if (!$this->shouldAdvanceBooking($nextBooking)) {
                continue;
            }

            $this->moveBookingFromWaitingList($nextBooking, $event);
        }
    }

    private function findNextEligibleBooking(CalendarEventsModel $event, int $availableSlots): CalendarEventsMemberModel|null
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('id')
            ->from('tl_calendar_events_member', 't')
            ->where('t.pid = :pid AND t.waitingList = 1 AND t.canceled != 1 AND t.expired != 1')
            ->andWhere('t.ticketAmount <= :availableSlots')
            ->andWhere($queryBuilder->expr()->notIn('t.id', ':processedIds'))
            ->setParameter('pid', $event->id)
            ->setParameter('availableSlots', $availableSlots)
            ->setParameter('processedIds', $this->processedIds, ArrayParameterType::INTEGER)
            ->orderBy('t.addedOn', 'ASC')
        ;

        if ($event->getRelated('pid')?->requireOptIn) {
            $queryBuilder->andWhere('t.optIn = 1');
        }

        $bookingID = $queryBuilder->fetchOne();

        if (false === $bookingID) {
            return null;
        }

        $this->processedIds[] = $bookingID;

        return CalendarEventsMemberModel::findById($bookingID);
    }

    private function shouldAdvanceBooking(CalendarEventsMemberModel $booking): bool
    {
        $advancementEvent = new WaitingListAdvancementEvent(
            $booking,
            self::class,
            $this->requestStack->getCurrentRequest(),
        );

        $this->eventDispatcher->dispatch($advancementEvent);

        return $advancementEvent->shouldAdvance();
    }

    private function moveBookingFromWaitingList(CalendarEventsMemberModel $booking, CalendarEventsModel $event): void
    {
        $affected = $this->connection->update(
            'tl_calendar_events_member',
            ['waitingList' => 0],
            ['id' => $booking->id],
        );

        if ($affected) {
            $this->sendAdvancementNotification($booking, $event);
            $this->logAdvancement($booking);
        }
    }

    private function sendAdvancementNotification(CalendarEventsMemberModel $booking, CalendarEventsModel $event): void
    {
        $calendar = $event->getRelated('pid');

        if ($calendar?->waitingListAdvancementNotification) {
            $this->notificationCenter->sendNotification(
                $calendar->waitingListAdvancementNotification,
                $this->notificationHelper->getNotificationTokens($booking),
            );
        }
    }

    private function logAdvancement(CalendarEventsMemberModel $booking): void
    {
        $this->contaoGeneralLogger?->info(
            \sprintf(
                'Moved booking ID %d from waiting list to the regular list of bookings.',
                $booking->id,
            ),
        );
    }
}
