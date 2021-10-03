<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
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
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function log(string $strText, string $strLevel): void
    {
        if (null !== $this->logger) {
            $this->logger->log(
                $strLevel,
                $strText,
                [
                    'contao' => new ContaoContext(__METHOD__, $level),
                ]
            );
        }
    }
}
