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

namespace Markocupic\CalendarEventBookingBundle\Domain\Unsubscribe;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UnsubscribeValidator
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function validate(CalendarEventsMemberModel|null $booking, bool $unsubscribedFlag, string $transDomain): ValidationResult
    {
        if (null === $booking) {
            return ValidationResult::fail(
                $this->translator->trans('mod_unsubscribe.error.invalid_uuid', [], $transDomain),
                SeverityLevel::ERROR->value,
                'error booking-not-found',
            );
        }

        $calEvent = $booking->getRelated('pid');

        if (!$calEvent instanceof CalendarEventsModel) {
            return ValidationResult::fail(
                $this->translator->trans('mod_unsubscribe.error.event_not_found', [], $transDomain),
                SeverityLevel::ERROR->value,
                'error event-not-found',
            );
        }

        $calendar = $calEvent->getRelated('pid');

        if (!$calendar instanceof CalendarModel) {
            return ValidationResult::fail(
                $this->translator->trans('mod_unsubscribe.error.calendar_not_found', [], $transDomain),
                SeverityLevel::ERROR->value,
                'error calendar-not-found',
            );
        }

        if (!$calendar->allowEventBooking) {
            return ValidationResult::fail(
                $this->translator->trans('mod_unsubscribe.error.booking_not_allowed_for_this_calendar', [], $transDomain),
                SeverityLevel::ERROR->value,
                'error booking-not-allowed-for-this-calednar',
            );
        }

        if ($booking->canceled) {
            $transKey = $unsubscribedFlag
                ? 'mod_unsubscribe.info.unsubscribe_success'
                : 'mod_unsubscribe.info.already_unsubscribed';

            return ValidationResult::fail(
                $this->translator->trans($transKey, ['%title%' => $calEvent->title], $transDomain),
                SeverityLevel::INFO->value,
                cssClass: 'info booking-already-canceled',
                flags: ['hasUnsubscribed' => true],
            );
        }

        if (!$calEvent->enableDeregistration) {
            return ValidationResult::fail(
                $this->translator->trans('mod_unsubscribe.error.unsubscription_not_allowed', ['%title%' => $calEvent->title], $transDomain),
                SeverityLevel::ERROR->value,
                'error unsubscription-not-allowed',
            );
        }

        if ($this->isLimitExpired($calEvent)) {
            return ValidationResult::fail(
                $this->translator->trans('mod_unsubscribe.error.unsubscription_limit_expired', ['%title%' => $calEvent->title], $transDomain),
                SeverityLevel::ERROR->value,
                'error unsubscription-limit-expired',
            );
        }

        return ValidationResult::ok($calEvent);
    }

    private function isLimitExpired(CalendarEventsModel $calEvent): bool
    {
        if (!empty($calEvent->unsubscribeLimitTstamp)) {
            return time() > $calEvent->unsubscribeLimitTstamp;
        }

        $limitDays = $calEvent->unsubscribeLimit > 0 ? $calEvent->unsubscribeLimit : 0;
        $limitTimestamp = $limitDays * 3600 * 24;

        if ($calEvent->addTime && $calEvent->startTime > $calEvent->startDate) {
            return time() > $calEvent->startTime - $limitTimestamp;
        }

        return strtotime('today 00:00') > $calEvent->startDate - $limitTimestamp;
    }
}
