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

namespace Markocupic\CalendarEventBookingBundle\Logger;

use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;

class Logger
{
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function log(string $strText, string $strLevel, $strContaoLevel): void
    {
        if (null !== $this->logger) {
            $this->logger->log(
                $strLevel,
                $strText,
                [
                    'contao' => new ContaoContext(__METHOD__, $strContaoLevel),
                ]
            );
        }
    }
}
