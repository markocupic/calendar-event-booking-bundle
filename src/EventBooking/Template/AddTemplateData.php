<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventBooking\Template;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\Template;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;
use Symfony\Component\Security\Core\Security;

final class AddTemplateData
{
    private Adapter $memberModelAdapter;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly BookingValidator $bookingValidator,
    ) {
        // Adapters
        $this->memberModelAdapter = $this->framework->getAdapter(MemberModel::class);
    }

    /**
     * Augment template with more event properties.
     *
     * @throws Exception
     */
    public function addTemplateData(EventConfig $eventConfig, Template $template): void
    {
        $template->canRegister = $this->bookingValidator->validateCanRegister($eventConfig);

        $template->isFullyBooked = $eventConfig->isFullyBooked();

        $template->numberFreeSeats = $eventConfig->getNumberOfFreeSeats();

        $template->numberFreeSeatsWaitingList = $eventConfig->getModel()->activateWaitingList ? $eventConfig->getNumberOfFreeSeats(true) : 0;

        $template->confirmedBookingsCount = $eventConfig->getConfirmedBookingsCount();

        $template->bookingMin = $eventConfig->getBookingMin();

        $template->bookingMax = $eventConfig->getBookingMax();

        $template->bookingStartDate = $eventConfig->getBookingStartDate('date');

        $template->bookingStartDatim = $eventConfig->getBookingStartDate('datim');

        $template->bookingStartTimestamp = $eventConfig->getBookingStartDate('timestamp');

        $template->getBookingEndDate = $eventConfig->getBookingEndDate('date');

        $template->getBookingEndDatim = $eventConfig->getBookingEndDate('datim');

        $template->getBookingEndTimestamp = $eventConfig->getBookingEndDate('timestamp');

        $template->hasLoggedInUser = $this->hasLoggedInFrontendUser();

        $template->getLoggedInUser = $this->getLoggedInFrontendUser() ? $this->getLoggedInFrontendUser()->row() : [];

        $template->event = $eventConfig->getModel()->row();

        $template->eventConfig = $eventConfig;

        // In your twig template:
        // {{ registrations.invoke() }} {# all registrations #}
        // {{ registrations.invoke(['cebb_booking_state_on_waiting_list', 'cebb_booking_state_confirmed']) }} {# filtered #}
        $template->registrations = static fn(array $filter = []): array => $eventConfig->getRegistrationsAsArray($filter);
    }

    private function hasLoggedInFrontendUser(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof FrontendUser;
    }

    private function getLoggedInFrontendUser(): MemberModel|null
    {
        $user = $this->security->getUser();

        if ($user instanceof FrontendUser) {
            return $this->memberModelAdapter->findByPk($user->id);
        }

        return null;
    }
}
