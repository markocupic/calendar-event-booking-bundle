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

namespace Markocupic\CalendarEventBookingBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Markocupic\CalendarEventBookingBundle\Event\AutoDeleteExpiredBookingEvent;
use Markocupic\CalendarEventBookingBundle\Event\AutoExpireReservedBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCronJob('minutely')]
class HandleTemporaryReservedBookingsCron
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly bool $autoExpireReservedBookings,
        private readonly bool $autoDeleteExpiredBookings,
        private readonly int $autoExpireTimeLimit,
        private readonly LoggerInterface|null $contaoCronLogger,
    ) {
    }

    public function __invoke(): void
    {
        if ($this->autoExpireTimeLimit < 0) {
            return;
        }

        if ($this->autoDeleteExpiredBookings) {
            $this->processAutoDelete();
        }

        if ($this->autoExpireReservedBookings) {
            $this->processAutoExpire();
        }
    }

    public function processAutoDelete(): void
    {
        $this->framework->initialize();

        $qb = $this->connection->createQueryBuilder();

        $qb->select('id')
            ->from('tl_calendar_events_member', 't')
            ->where('t.expired = 1')
            ->setMaxResults(50)
        ;

        $bookingIDS = $qb->fetchFirstColumn();

        foreach ($bookingIDS as $bookingID) {
            $request = $this->requestStack->getCurrentRequest();
            $model = CalendarEventsMemberModel::findByPk($bookingID);

            $event = new AutoDeleteExpiredBookingEvent($model, self::class, $request);
            $this->eventDispatcher->dispatch($event);

            if (!$event->shouldDelete()) {
                continue;
            }

            if ($model->delete()) {
                $this->contaoCronLogger->info("Expired booking ID $bookingID has been deleted automatically.");
            }
        }
    }

    public function processAutoExpire(): void
    {
        $this->framework->initialize();

        $timeCut = time() - $this->autoExpireTimeLimit;

        $qb = $this->connection->createQueryBuilder();

        $qb->select('id')
            ->from('tl_calendar_events_member', 't')
            ->where('t.temporaryReserved = 1 AND t.expired = 0 AND t.addedOn != ""')
            ->andWhere('t.addedOn < :timeCut')
            ->setParameter('timeCut', $timeCut, ParameterType::INTEGER)
            ->setMaxResults(50)
        ;

        $bookingIDS = $qb->fetchFirstColumn();

        foreach ($bookingIDS as $bookingID) {
            $request = $this->requestStack->getCurrentRequest();
            $model = CalendarEventsMemberModel::findByPk($bookingID);

            $event = new AutoExpireReservedBookingEvent($model, self::class, $request);
            $this->eventDispatcher->dispatch($event);

            if (!$event->shouldExpire()) {
                continue;
            }

            $set = [
                'expired' => 1,
                'temporaryReserved' => 0,
            ];

            $affected = $this->connection->update('tl_calendar_events_member', $set, ['id' => $bookingID], [Types::INTEGER]);

            if (!$affected) {
                continue;
            }

            $this->contaoCronLogger->info("Temporary reserved booking ID $bookingID has been expired automatically.");
        }
    }
}
