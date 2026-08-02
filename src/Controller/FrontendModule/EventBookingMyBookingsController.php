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
    public const string TYPE = 'event_booking_my_bookings';

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
        // Restrict the sort direction to a fixed whitelist to prevent SQL injection
        // via the module configuration.
        $sorting = 'DESC' === strtoupper((string) $model->ceb_modMyBookings_sorting) ? 'DESC' : 'ASC';

        $qb = $this->connection->createQueryBuilder()
            ->select('cem.*')
            ->from('tl_calendar_events_member', 'cem')
            ->join('cem', 'tl_calendar_events', 'ce', 'cem.pid = ce.id')
            ->where('cem.member = :memberId')
            ->setParameter('memberId', $user->id, Types::INTEGER)
            ->orderBy('ce.startDate', $sorting)
        ;

        if ('past' === $model->ceb_modMyBookings_startTimeFilter) {
            $qb
                ->andWhere('ce.startDate < :pastLimit')
                ->setParameter('pastLimit', strtotime('+ 1 day'), Types::INTEGER)
            ;
        }

        if ('upcoming' === $model->ceb_modMyBookings_startTimeFilter) {
            $qb
                ->andWhere('ce.startDate > :upcomingLimit')
                ->setParameter('upcomingLimit', strtotime('- 1 day'), Types::INTEGER)
            ;
        }

        $bookings = $qb->fetchAllAssociative();

        $rows = [];

        foreach ($bookings as $rowBooking) {
            $booking = $this->getContaoAdapter(CalendarEventsMemberModel::class)->findById($rowBooking['id']);

            if (null === $booking) {
                continue;
            }

            $calEvent = $booking->getRelated('pid');

            if (null === $calEvent) {
                continue;
            }

            $calendar = $calEvent->getRelated('pid');
            $payments = $this->getContaoAdapter(CalendarEventsPaymentModel::class)->findByPid($rowBooking['id']);

            // Reset the figure on every iteration, otherwise a booking without an image
            // would inherit the figure of a previous booking.
            $figure = null;

            if ($model->ceb_addImage && $calEvent->addImage) {
                $figure = $this->figureUtil->buildFigure($calEvent->row());
            }

            $rows[] = [
                'booking' => $booking,
                'event' => $calEvent,
                'calendar' => $calendar,
                'payments' => $payments,
                'figure' => $figure,
            ];
        }

        return $rows;
    }
}
