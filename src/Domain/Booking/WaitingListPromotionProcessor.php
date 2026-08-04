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

namespace Markocupic\CalendarEventBookingBundle\Domain\Booking;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
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
class WaitingListPromotionProcessor
{
    private array $processedIds = [0];

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly BookingCapacity $bookingCapacity,
        private readonly LockFactory $lockFactory,
        private readonly NotificationCenter $notificationCenter,
        private readonly NotificationService $notificationService,
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

    public function promoteBookingFromWaitingList(CalendarEventsMemberModel $booking, CalendarEventsModel $event, string $context): void
    {
        $affected = $this->connection->update(
            'tl_calendar_events_member',
            ['waitingList' => 0],
            ['id' => $booking->id],
        );

        if ($affected) {
            $this->sendPromotionNotification($booking, $event);
            $this->logPromotion($booking, $context);
        }
    }

    private function sendPromotionNotification(CalendarEventsMemberModel $booking, CalendarEventsModel $event): void
    {
        $calendar = $event->getRelated('pid');

        if ($calendar?->waitingListAdvancementNotification) {
            $this->notificationCenter->sendNotification(
                $calendar->waitingListAdvancementNotification,
                $this->notificationService->getNotificationTokens($booking),
            );
        }
    }

    private function logPromotion(CalendarEventsMemberModel $booking, string $context): void
    {
        $this->contaoGeneralLogger?->info(
            \sprintf(
                'Moved booking ID %d from waiting list to the regular list of bookings. Context: %s',
                $booking->id,
                $context,
            ),
        );
    }

    private function getEventsToProcess(CalendarEventsModel|null $event): Collection|null
    {
        if ($event) {
            return new Collection([$event], CalendarEventsMemberModel::getTable());
        }

        $calendarIds = $this->framework->getAdapter(CalendarModel::class)->findAll()?->fetchEach('id') ?? [];

        return $this->framework->getAdapter(CalendarEventsModel::class)->findUpcomingByPids($calendarIds);
    }

    private function processWaitingListForEvent(CalendarEventsModel $calendarEvent): void
    {
        while (($availableSlots = $calendarEvent->maxBookings - $this->bookingCapacity->getBookingCount($calendarEvent)) > 0) {
            $nextBooking = $this->findNextEligibleBooking($calendarEvent, $availableSlots);

            if (null === $nextBooking) {
                break;
            }

            $event = new WaitingListPromotedEvent(
                $nextBooking,
                self::class,
                $this->requestStack->getCurrentRequest(),
            );

            // It is the responsibility of the corresponding event listener to call
            // WaitingListPromotionProcessor::promoteBookingFromWaitingList() in order to remove the
            // ‘waiting list’ status from the booking.
            $this->eventDispatcher->dispatch($event);
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

        return $this->framework->getAdapter(CalendarEventsMemberModel::class)->findById($bookingID);
    }
}
