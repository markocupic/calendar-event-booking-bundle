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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Markocupic\ExportTable\Config\Config;
use Markocupic\ExportTable\Export\ExportTable;
use Markocupic\ExportTable\Writer\ByteSequence;
use Symfony\Component\HttpFoundation\RequestStack;

class CalendarEventsMember
{
    public const TABLE = 'tl_calendar_events_member';

    // Adapters
    private Adapter $system;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly ExportTable $exportTable,
    ) {
        // Adapters
        $this->system = $this->framework->getAdapter(System::class);
    }

    /**
     * Download the registration list as a csv spreadsheet.
     *
     * @throws \Exception
     */
    #[AsCallback(table: self::TABLE, target: 'config.onload')]
    public function downloadRegistrationList(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ('downloadRegistrationList' === $request->query->get('action')) {
            $arrSkip = ['bookingToken'];
            $arrSelectedFields = [];

            foreach (array_keys($GLOBALS['TL_DCA'][self::TABLE]['fields']) as $k) {
                if (!\in_array($k, $arrSkip, true)) {
                    $arrSelectedFields[] = $k;
                }
            }

            $exportConfig = (new Config(self::TABLE))
                ->setExportType('csv')
                ->setFilter([[self::TABLE.'.pid = ?'], [$request->query->get('id')]])
                ->setFields($arrSelectedFields)
                ->setAddHeadline(true)
                ->setHeadlineFields($arrSelectedFields)
                ->setOutputBom(ByteSequence::BOM['UTF-8'])
                ;

            $this->exportTable->run($exportConfig);
        }
    }

    /**
     * Trigger the bookingStateChange HOOK.
     *
     * @throws Exception
     */
    #[AsCallback(table: self::TABLE, target: 'fields.bookingState.save')]
    public function triggerBookingStateChangeHook(string $strBookingStateNew, DataContainer $dc): string
    {
        $arrEventMember = $this->connection->fetchAssociative('SELECT * FROM tl_calendar_events_member WHERE id = ?', [$dc->id]);

        if (false !== $arrEventMember) {
            if ($strBookingStateNew !== $arrEventMember['bookingState']) {
                $intId = (int) $arrEventMember['id'];
                $strBookingStateOld = $arrEventMember['bookingState'];

                if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_BOOKING_STATE_CHANGE]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_BOOKING_STATE_CHANGE])) {
                    foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_BOOKING_STATE_CHANGE] as $callback) {
                        $strBookingStateNew = $this->system->importStatic($callback[0])->{$callback[1]}($strBookingStateOld, $strBookingStateNew, $intId);
                    }
                }
            }
        }

        return $strBookingStateNew;
    }
}
