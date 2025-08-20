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

namespace Markocupic\CalendarEventBookingBundle\DataContainer;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Date;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Helper\EventStatus;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalendarEvents
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly EventStatus $eventStatus,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Adjust bookingStartDate and bookingStartDate.
     */
    #[AsCallback(table: 'tl_calendar_events', target: 'config.onsubmit')]
    public function adjustBookingDate(DataContainer $dc): void
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord) {
            return;
        }

        $arrSet['bookingStartDate'] = $dc->activeRecord->bookingStartDate ?: null;
        $arrSet['bookingEndDate'] = $dc->activeRecord->bookingEndDate ?: null;

        // Set the end date
        if (!empty((int) $dc->activeRecord->bookingEndDate)) {
            if ($dc->activeRecord->bookingEndDate < $dc->activeRecord->bookingStartDate) {
                $arrSet['bookingEndDate'] = $dc->activeRecord->bookingStartDate;
                $message = $this->framework->getAdapter(Message::class);
                $message->addInfo($this->translator->trans('MSC.adjusted_booking_period_end_time', [], 'contao_default'));
            }
        }

        $this->connection->update('tl_calendar_events', $arrSet, ['id' => $dc->id]);
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'list.sorting.child_record')]
    public function listEvents(array $arrRow): string
    {
        $markup = (new \tl_calendar_events())->listEvents($arrRow);

        if ($arrRow['enableBookingForm']) {
            $booking = $this->framework->getAdapter(CalendarEventsModel::class)->findById($arrRow['id']);
            $bookingCount = $this->eventStatus->getBookingCount($booking, $this->connection);
            $counterMarkup = \sprintf(
                '<span class="label-info">[%s %sx]</span>',
                $this->translator->trans('MSC.bookings', [], 'contao_default'),
                $bookingCount,
            );
            $markup = str_replace('</div>', $counterMarkup.'</div>', $markup);
        }

        return $markup;
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'fields.netPrice.load')]
    #[AsCallback(table: 'tl_calendar_events', target: 'fields.taxValue.load')]
    public function loadAsDoublePrecision(float $value, DataContainer $dc): string
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * Check whether the timestamp entered makes sense in relation to the event start
     * and end times.
     */
    #[AsCallback(table: 'tl_calendar_events', target: 'fields.unsubscribeLimitTstamp.save')]
    public function saveUnsubscribeLimitTstamp(int|null $intValue, DataContainer $dc): int|null
    {
        if (!empty($intValue)) {
            // Check whether we have an unsubscribeLimit (in days) set as well, notify the
            // user that we cannot have both
            if ($dc->activeRecord->unsubscribeLimit > 0) {
                throw new \InvalidArgumentException($this->translator->trans('ERR.conflicting_unsubscribe_deadlines', [], 'contao_default'));
            }

            $date = $this->framework->getAdapter(Date::class);

            // If the event has an end date (and optional time), that's the last sensible
            // time unsubscription makes sense
            if ($dc->activeRecord->endDate) {
                if ($dc->activeRecord->addTime) {
                    $intMaxValue = (int) strtotime(\sprintf(
                        '%s %s',
                        $date->parse('Y-m-d', $dc->activeRecord->endDate),
                        $date->parse('H:i:s', $dc->activeRecord->endTime),
                    ));
                } else {
                    $intMaxValue = (int) $dc->activeRecord->endDate + 86400;
                }
            } else {
                if ($dc->activeRecord->addTime) {
                    $intMaxValue = (int) strtotime(\sprintf(
                        '%s %s',
                        $date->parse('Y-m-d', $dc->activeRecord->startDate),
                        $date->parse('H:i:s', $dc->activeRecord->startTime),
                    ));
                } else {
                    $intMaxValue = (int) $dc->activeRecord->startDate + 86400;
                }
            }

            if ($intValue > $intMaxValue) {
                throw new \InvalidArgumentException($this->translator->trans('ERR.invalid_unsubscription_deadline', [], 'contao_default'));
            }
        }

        return $intValue;
    }
}
