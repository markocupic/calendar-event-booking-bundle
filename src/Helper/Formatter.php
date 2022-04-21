<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;

class Formatter
{
    private ContaoFramework $framework;

    // Adapters
    private Adapter $controller;
    private Adapter $date;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;

        // Adapters
        $this->controller = $this->framework->getAdapter(Controller::class);
        $this->date = $this->framework->getAdapter(Date::class);
    }

    /**
     * @param $varValue
     *
     * @throws \Exception
     *
     * @return int|mixed|string
     */
    public function convertDateFormatsToTimestamps($varValue, string $strTable, string $strFieldName)
    {
        $rgxp = $this->getFieldRgxp($strTable, $strFieldName);

        // Convert date formats into timestamps
        if (null !== $varValue && '' !== $varValue && \in_array($rgxp, ['date', 'time', 'datim'], true)) {
            try {
                $objDate = new Date($varValue, $this->date->getFormatFromRgxp($rgxp));
                $varValue = $objDate->tstamp;
            } catch (\OutOfBoundsException $e) {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['invalidDate'], $varValue));
            }
        }

        return $varValue;
    }

    public function formatEmail($varValue, string $strTable, string $strFieldName)
    {
        $rgxp = $this->getFieldRgxp($strTable, $strFieldName);

        if (null !== $varValue && '' !== $varValue && 'email' === $rgxp) {
            $varValue = strtolower($varValue);
        }

        return $varValue;
    }

    public function getCorrectEmptyValue($varValue, string $strTable, string $strFieldName)
    {
        $this->controller->loadDataContainer($strTable);

        if (isset($GLOBALS['TL_DCA'][$strTable]['fields'][$strFieldName]['default'])) {
            if (null === $varValue || '' === $varValue) {
                return $GLOBALS['TL_DCA'][$strTable]['fields'][$strFieldName]['default'];
            }
        }

        return $varValue;
    }

    private function getFieldRgxp(string $strTable, string $strFieldName): ?string
    {
        $this->controller->loadDataContainer($strTable);

        return $GLOBALS['TL_DCA'][$strTable]['fields'][$strFieldName]['eval']['rgxp'] ?? null;
    }
}
