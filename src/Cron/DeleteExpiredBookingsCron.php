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
use Doctrine\DBAL\Types\Types;
use Markocupic\CalendarEventBookingBundle\Event\AutoDeleteExpiredBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCronJob('minutely')]
class DeleteExpiredBookingsCron
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly bool $autoDeleteExpiredBookings,
        private readonly LoggerInterface|null $contaoCronLogger,
    ) {
    }

    public function __invoke(): void
    {
        $this->processAutoDelete();
    }

    public function processAutoDelete(): void
    {
        if (!$this->autoDeleteExpiredBookings) {
            return;
        }

        $this->framework->initialize();

        $qb = $this->connection->createQueryBuilder();
        $qb->select('id')
            ->from('tl_calendar_events_member', 't')
            ->where('t.expired = 1')
        ;

        $bookingIds = $qb->fetchFirstColumn();
        $request = $this->requestStack->getCurrentRequest();

        foreach ($bookingIds as $bookingId) {
            $this->processSingleExpiredBooking($bookingId, $request);
        }
    }

    private function processSingleExpiredBooking(int $bookingId, Request|null $request): bool
    {
        $model = $this->framework->getAdapter(CalendarEventsMemberModel::class)->findById($bookingId);

        if (null === $model) {
            return false;
        }

        $event = new AutoDeleteExpiredBookingEvent($model, self::class, $request);
        $this->eventDispatcher->dispatch($event);

        if (false === $event->shouldDelete()) {
            return false;
        }

        if ($this->connection->delete('tl_calendar_events_member', ['id' => $bookingId], [Types::INTEGER])) {
            $this->contaoCronLogger->info("Expired booking ID $bookingId has been deleted automatically.");

            return true;
        }

        return false;
    }
}
