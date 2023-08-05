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

use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Markocupic\CalendarEventBookingBundle\Exception\DcaFieldNotFoundException;

class DcaUtil
{
    private Adapter $controller;

    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
        $this->controller = $this->framework->getAdapter(Controller::class);
    }

    public function pushClass(string $fieldName, string $tableName, string $class): void
    {
        $this->controller->loadDataContainer($tableName);

        if (!isset($GLOBALS['TL_DCA'][$tableName]['fields'][$fieldName])) {
            throw new DcaFieldNotFoundException(sprintf('DCA field "%s.%s" not found.', $fieldName, 'tl_calendar'));
        }

        $strClass = $GLOBALS['TL_DCA']['tl_calendar']['fields'][$fieldName]['eval']['tl_class'] ?? '';

        $GLOBALS['TL_DCA']['tl_calendar']['fields'][$fieldName]['eval']['tl_class'] = $strClass.' '.$class;
    }
}
