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

namespace Markocupic\CalendarEventBookingBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Markocupic\CalendarEventBookingBundle\Event\AutoExpireReservedBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCronJob('minutely')]
class HandleExpirableBookingsCron
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly bool $autoExpireReservedBookings,
        private readonly int $autoExpireTimeLimit,
        private readonly LoggerInterface|null $contaoCronLogger,
    ) {
    }

    public function __invoke(): void
    {
        $this->processAutoExpire();
    }

    public function processAutoExpire(): void
    {
        if (!$this->autoExpireReservedBookings) {
            return;
        }

        if ($this->autoExpireTimeLimit < 0) {
            return;
        }

        $this->framework->initialize();

        $timeCutoff = time() - $this->autoExpireTimeLimit;

        $bookingIds = $this->fetchExpirableBookings($timeCutoff);
        $request = $this->requestStack->getCurrentRequest();

        foreach ($bookingIds as $bookingId) {
            $this->processSingleBookingExpiration($bookingId, $request);
        }
    }

    private function fetchExpirableBookings(int $timeCutoff): array
    {
        $qb = $this->connection->createQueryBuilder();

        return $qb->select('id')
            ->from('tl_calendar_events_member', 't')
            ->where('t.temporaryReserved = 1 AND t.expired = 0 AND t.addedOn != ""')
            ->andWhere('t.addedOn < :timeCutoff')
            ->setParameter('timeCutoff', $timeCutoff, ParameterType::INTEGER)
            ->fetchFirstColumn()
        ;
    }

    private function processSingleBookingExpiration(int $bookingId, Request|null $request): void
    {
        $model = $this->framework->getAdapter(CalendarEventsMemberModel::class)->findById($bookingId);

        $event = new AutoExpireReservedBookingEvent($model, self::class, $request);
        $this->eventDispatcher->dispatch($event);

        if (!$event->shouldExpire()) {
            return;
        }

        $affected = $this->connection->update(
            'tl_calendar_events_member',
            ['expired' => 1, 'temporaryReserved' => 0],
            ['id' => $bookingId],
            [Types::INTEGER],
        );

        if ($affected) {
            $this->contaoCronLogger->info("Temporary reserved booking ID $bookingId has been expired automatically.");
        }
    }
}
