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

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Event\WaitingListPromotedEvent;
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
        private readonly EventStatus $eventStatus,
        private readonly LockFactory $lockFactory,
        private readonly NotificationCenter $notificationCenter,
        private readonly NotificationManager $notificationManager,
        private readonly RequestStack $requestStack,
        private readonly bool $autoWaitingListPromotion,
        private readonly LoggerInterface|null $contaoGeneralLogger,
    ) {
    }

    public function checkWaitingList(CalendarEventsModel|null $event = null): void
    {
        if (!$this->autoWaitingListPromotion) {
            return;
        }

        $this->framework->initialize();

        $lock = $this->lockFactory->createLock(self::class);
        $lock->acquire(true);

        try {
            $events = $this->getEventsToProcess($event);

            if (null === $events) {
                return;
            }

            while ($events->next()) {
                /** @var CalendarEventsModel $currentEvent */
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

    public function sendPromotionNotification(CalendarEventsMemberModel $booking, CalendarEventsModel $event): void
    {
        $calendar = $event->getRelated('pid');

        if ($calendar?->waitingListAdvancementNotification) {
            $this->notificationCenter->sendNotification(
                $calendar->waitingListAdvancementNotification,
                $this->notificationManager->getNotificationTokens($booking),
            );
        }
    }

    public function logPromotion(CalendarEventsMemberModel $booking): void
    {
        $this->contaoGeneralLogger?->info(
            \sprintf(
                'Moved booking ID %d from waiting list to the regular list of bookings.',
                $booking->id,
            ),
        );
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
        while (($availableSlots = $event->maxBookings - $this->eventStatus->getBookingCount($event, $this->connection)) > 0) {
            $nextBooking = $this->findNextEligibleBooking($event, $availableSlots);

            if (null === $nextBooking) {
                break;
            }

            if (!$this->isAdvancementAllowed($nextBooking)) {
                continue;
            }

            $this->promoteBookingFromWaitingList($nextBooking, $event);
        }
    }

    private function findNextEligibleBooking(CalendarEventsModel $event, int $availableSlots): CalendarEventsMemberModel|null
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('id')
            ->from('tl_calendar_events_member', 't')
            ->where('t.pid = :pid AND t.waitingList = 1 AND temporaryReserved != 1 AND t.canceled != 1 AND t.expired != 1')
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

    private function isAdvancementAllowed(CalendarEventsMemberModel $booking): bool
    {
        $event = new WaitingListPromotedEvent(
            $booking,
            self::class,
            $this->requestStack->getCurrentRequest(),
        );

        $this->eventDispatcher->dispatch($event);

        return $event->isAdvancementAllowed();
    }

    private function promoteBookingFromWaitingList(CalendarEventsMemberModel $booking, CalendarEventsModel $event): void
    {
        $affected = $this->connection->update(
            'tl_calendar_events_member',
            ['waitingList' => 0],
            ['id' => $booking->id],
        );

        if ($affected) {
            $this->sendPromotionNotification($booking, $event);
            $this->logPromotion($booking);
        }
    }
}
