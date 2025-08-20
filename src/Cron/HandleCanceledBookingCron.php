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
use Markocupic\CalendarEventBookingBundle\Event\AutoDeleteCanceledBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCronJob('minutely')]
class HandleCanceledBookingCron
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly bool $autoDeleteCanceledBookings,
        private readonly LoggerInterface|null $contaoCronLogger,
    ) {
    }

    public function __invoke(): void
    {
        if ($this->autoDeleteCanceledBookings) {
            $this->processAutoDelete();
        }
    }

    public function processAutoDelete(): void
    {
        $this->framework->initialize();

        $qb = $this->connection->createQueryBuilder();

        $qb->select('id')
            ->from('tl_calendar_events_member', 't')
            ->where('t.canceled = 1')
            ->setMaxResults(50)
        ;

        $bookingIDS = $qb->fetchFirstColumn();

        foreach ($bookingIDS as $bookingID) {
            $request = $this->requestStack->getCurrentRequest();
            $model = CalendarEventsMemberModel::findById($bookingID);

            $event = new AutoDeleteCanceledBookingEvent($model, self::class, $request);
            $this->eventDispatcher->dispatch($event);

            if (!$event->shouldDelete()) {
                continue;
            }

            if ($model->delete()) {
                $this->contaoCronLogger->info("Canceled booking ID $bookingID has been deleted automatically.");
            }
        }
    }
}
