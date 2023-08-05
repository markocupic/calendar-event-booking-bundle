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

namespace Markocupic\CalendarEventBookingBundle\DataContainer;

use Contao\Calendar;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Date;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalendarEvents
{
    public const TABLE = 'tl_calendar_events';

    private Adapter $calendar;
    private Adapter $calendarEventsMemberModel;
    private Adapter $config;
    private Adapter $date;
    private Adapter $message;
    private Adapter $controller;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly EventFactory $eventFactory,
        private readonly TranslatorInterface $translator,
    ) {
        $this->calendar = $this->framework->getAdapter(Calendar::class);
        $this->calendarEventsMemberModel = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $this->config = $this->framework->getAdapter(Config::class);
        $this->date = $this->framework->getAdapter(Date::class);
        $this->message = $this->framework->getAdapter(Message::class);
        $this->controller = $this->framework->getAdapter(Controller::class);
    }

    /**
     * Remove some fields if reg settings
     * should be taken from parent.
     *
     * @throws Exception
     */
    #[AsCallback(table: self::TABLE, target: 'config.onload')]
    public function adjustSubPalettes(DataContainer $dc): void
    {

        $arrRemove = [];

        $overrideByParent = (bool) $this->connection->fetchOne('SELECT overrideByParent FROM tl_calendar_events WHERE id = ?', [$dc->id]);

        if ($overrideByParent) {
            $this->controller->loadDataContainer('tl_calendar_events');
            $arrFields = array_keys($GLOBALS['TL_DCA']['tl_calendar_events']['fields']);

            foreach ($arrFields as $fieldName) {
                $allowOverriding = $GLOBALS['TL_DCA']['tl_calendar_events']['fields'][$fieldName]['eval']['allowOverrideByParent'] ?? false;

                if (true === $allowOverriding) {
                    $arrRemove[] = $fieldName;
                }
            }
        }

        if (!empty($arrRemove)) {
            $arrSubPal = array_keys($GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes'] ?? []);

            $pm = PaletteManipulator::create()
                ->removeField($arrRemove)
            ;

            foreach ($arrSubPal as $subPalName) {
                $pm->applyToSubPalette($subPalName, 'tl_calendar_events');
            }
        }
    }

    /**
     * Adjust bookingStartDate and bookingEndDate.
     *
     * @throws Exception
     */
    #[AsCallback(table: self::TABLE, target: 'config.onsubmit')]
    public function adjustBookingDate(DataContainer $dc): void
    {

        // Return if there is no active record (override all)
        if (!$dc->activeRecord) {
            return;
        }

        $arrSet['bookingStartDate'] = $dc->activeRecord->bookingStartDate ?: null;
        $arrSet['bookingEndDate'] = $dc->activeRecord->bookingEndDate ?: null;

        // Set end date
        if (!empty((int) $dc->activeRecord->bookingEndDate)) {
            if ($dc->activeRecord->bookingEndDate < $dc->activeRecord->bookingStartDate) {
                $arrSet['bookingEndDate'] = $dc->activeRecord->bookingStartDate;
                $this->message->addInfo($GLOBALS['TL_LANG']['MSC']['adjusted_booking_period_end_time']);
            }
        }

        $this->connection->update(self::TABLE, $arrSet, ['id' => $dc->id]);
    }

    #[AsCallback(table: self::TABLE, target: 'fields.text.save')]
    public function saveUnsubscribeLimitTstamp(int|null $intValue, DataContainer $dc): int|null
    {

        if (!empty($intValue)) {
            // Check whether we have an unsubscribeLimit (in days) set as well, notify the user that we cannot have both
            if ($dc->activeRecord->unsubscribeLimit > 0) {
                throw new \InvalidArgumentException($GLOBALS['TL_LANG']['ERR']['conflicting_unsubscribe_limits']);
            }

            // Check whether the timestamp entered makes sense in relation to the event start and end times
            // If the event has an end date (and optional time) that's the last sensible time unsubscription makes sense
            if ($dc->activeRecord->endDate) {
                if ($dc->activeRecord->addTime) {
                    $intMaxValue = (int) strtotime(date('Y-m-d', (int) $dc->activeRecord->endDate).' '.date('H:i:s', (int) $dc->activeRecord->endTime));
                } else {
                    $intMaxValue = (int) $dc->activeRecord->endDate;
                }
            } else {
                if ($dc->activeRecord->addTime) {
                    $intMaxValue = (int) strtotime(date('Y-m-d', (int) $dc->activeRecord->startDate).' '.date('H:i:s', (int) $dc->activeRecord->startTime));
                } else {
                    $intMaxValue = (int) $dc->activeRecord->startDate;
                }
            }

            if ($intValue > $intMaxValue) {
                throw new \InvalidArgumentException($GLOBALS['TL_LANG']['ERR']['invalid_unsubscription_limit']);
            }
        }

        return $intValue;
    }

    #[AsCallback(table: self::TABLE, target: 'list.sorting.child_record', priority: 100)]
    public function childRecordCallback(array $arrRow): string
    {

        $origClass = new \tl_calendar_events();

        $strRegistrationsBadges = $this->getBookingStateBadgesString($arrRow);

        if ($strRegistrationsBadges) {
            $arrRow['title'] .= $strRegistrationsBadges;
        }

        return $origClass->listEvents($arrRow);
    }

    private function getBookingStateBadgesString(array $arrRow): string
    {

        $strRegistrationsBadges = '';

        $intNotConfirmed = 0;
        $intConfirmed = 0;
        $intRejected = 0;
        $intWaitingList = 0;
        $intUnsubscribed = 0;
        $intUndefined = 0;

        $eventsMemberModel = $this->calendarEventsMemberModel->findByPid($arrRow['id']);

        if (null !== $eventsMemberModel) {
            while ($eventsMemberModel->next()) {
                if (BookingState::STATE_NOT_CONFIRMED === $eventsMemberModel->bookingState) {
                    ++$intNotConfirmed;
                } elseif (BookingState::STATE_CONFIRMED === $eventsMemberModel->bookingState) {
                    ++$intConfirmed;
                } elseif (BookingState::STATE_REJECTED === $eventsMemberModel->bookingState) {
                    ++$intRejected;
                } elseif (BookingState::STATE_WAITING_LIST === $eventsMemberModel->bookingState) {
                    ++$intWaitingList;
                } elseif (BookingState::STATE_UNSUBSCRIBED === $eventsMemberModel->bookingState) {
                    ++$intUnsubscribed;
                } elseif (BookingState::STATE_UNDEFINED === $eventsMemberModel->bookingState) {
                    ++$intUndefined;
                } else {
                    ++$intUndefined;
                }
            }

            if ($intNotConfirmed > 0) {
                $strRegistrationsBadges .= sprintf('<span class="subscription-badge not-confirmed blink" title="%dx %s">%sx</span>', $intNotConfirmed, $this->translator->trans('MSC.'.BookingState::STATE_NOT_CONFIRMED, [], 'contao_default'), $intNotConfirmed);
            }

            if ($intConfirmed > 0) {
                $strRegistrationsBadges .= sprintf('<span class="subscription-badge confirmed" title="%dx %s">%dx</span>', $intConfirmed, $this->translator->trans('MSC.'.BookingState::STATE_CONFIRMED, [], 'contao_default'), $intConfirmed);
            }

            if ($intRejected > 0) {
                $strRegistrationsBadges .= sprintf('<span class="subscription-badge rejected" title="%dx %s">%dx</span>', $intRejected, $this->translator->trans('MSC.'.BookingState::STATE_REJECTED, [], 'contao_default'), $intRejected);
            }

            if ($intWaitingList > 0) {
                $strRegistrationsBadges .= sprintf('<span class="subscription-badge waiting-list" title="%dx %s">%dx</span>', $intWaitingList, $this->translator->trans('MSC.'.BookingState::STATE_WAITING_LIST, [], 'contao_default'), $intWaitingList);
            }

            if ($intUnsubscribed > 0) {
                $strRegistrationsBadges .= sprintf('<span class="subscription-badge unsubscribed" title="%dx %s">%dx</span>', $intUnsubscribed, $this->translator->trans('MSC.'.BookingState::STATE_UNSUBSCRIBED, [], 'contao_default'), $intUnsubscribed);
            }

            if ($intUndefined > 0) {
                $strRegistrationsBadges .= sprintf('<span class="subscription-badge undefined" title="%dx %s">%sx</span>', $intUndefined, $this->translator->trans('MSC.'.BookingState::STATE_UNDEFINED, [], 'contao_default'), $intUndefined);
            }
        }

        return $strRegistrationsBadges;
    }
}
