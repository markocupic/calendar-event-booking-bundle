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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\PostBooking;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook(GenerateMessage::HOOK, priority: 900)]
final class GenerateMessage extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_POST_BOOKING;

    // Adapters
    private Adapter $message;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    ) {
        // Adapters
        $this->message = $this->framework->getAdapter(Message::class);
    }

    /**
     * Generate a short message for the frontend on post booking.
     *
     * @throws \Exception
     */
    public function __invoke(EventConfig $eventConfig, EventRegistration $eventRegistration): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $bookingState = $this->connection->fetchOne('SELECT bookingState FROM tl_calendar_events_member WHERE id = ?', [$eventRegistration->getModel()->id]);

        if (false === $bookingState) {
            return;
        }

        $msg = '';

        switch ($bookingState) {
            case BookingState::STATE_NOT_CONFIRMED:
            case BookingState::STATE_CONFIRMED:
            case BookingState::STATE_WAITING_LIST:
                 $msg = $this->translator->trans('MSC.post_booking_confirm_'.$bookingState, [$eventConfig->getModel()->title], 'contao_default');
                break;
        }

        if ($msg) {
            $this->message->addConfirmation($msg);
        }
    }
}
