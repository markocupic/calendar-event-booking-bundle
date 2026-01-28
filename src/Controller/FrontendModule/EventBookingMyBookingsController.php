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

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsPaymentModel;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(EventBookingMyBookingsController::TYPE, category: 'events')]
class EventBookingMyBookingsController extends AbstractFrontendModuleController
{
    public const TYPE = 'event_booking_my_bookings';

    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
        private readonly FigureUtil $figureUtil,
    ) {
    }

    /**
     * @throws \Exception
     */
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof FrontendUser) {
            $template->set('bookings', []);
        } else {
            $template->set('bookings', $this->getRelatedSubscriptions($user, $model));
        }

        return $template->getResponse();
    }

    private function getRelatedSubscriptions(FrontendUser $user, ModuleModel $model): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('cem.*')
            ->from('tl_calendar_events_member', 'cem')
            ->join('cem', 'tl_calendar_events', 'ce', 'cem.pid = ce.id')
            ->where('cem.member = :memberId')
            ->setParameter('memberId', $user->id, Types::INTEGER)
            ->orderBy('ce.startDate', $model->ceb_modMyBookings_sorting)
        ;

        if ('past' === $model->ceb_modMyBookings_startTimeFilter) {
            $qb->andWhere('ce.startDate < '.strtotime('+ 1 day'));
        }

        if ('upcoming' === $model->ceb_modMyBookings_startTimeFilter) {
            $qb->andWhere('ce.startDate > '.strtotime('- 1 day'));
        }

        $bookings = $qb->fetchAllAssociative();

        $rows = [];

        foreach ($bookings as $rowBooking) {
            $booking = $this->getContaoAdapter(CalendarEventsMemberModel::class)->findById($rowBooking['id']);
            $event = $booking->getRelated('pid');
            $calendar = $event?->getRelated('pid');
            $payments = $this->getContaoAdapter(CalendarEventsPaymentModel::class)->findByPid($rowBooking['id']);

            if ($model->ceb_addImage && $event->addImage) {
                $figure = $this->figureUtil->buildFigure($event->row());
            }

            $row = [
                'booking' => $booking,
                'event' => $event,
                'calendar' => $calendar,
                'payments' => $payments,
                'figure' => $figure ?? null,
            ];

            $rows[] = $row;
        }

        return $rows;
    }
}
