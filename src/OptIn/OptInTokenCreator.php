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

namespace Markocupic\CalendarEventBookingBundle\OptIn;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\OptIn\OptInToken;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\OptInModel;

class OptInTokenCreator
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function create($optInToken, int $removeOn, string $email, string $emailSubject, string $emailText, array $related): OptInTokenInterface
    {
        $this->framework->initialize();

        $optIn = $this->framework->createInstance(OptInModel::class);
        $optIn->tstamp = time();
        $optIn->token = $optInToken;
        $optIn->createdOn = time();
        // The token is required to invalidate/expire unconfirmed subscriptions after a configurable period
        // -> markocupic_calendar_event_booking.auto_expire_time_limit
        $optIn->removeOn = $removeOn;
        $optIn->email = $email;
        $optIn->emailSubject = $emailSubject;
        $optIn->emailText = $emailText;
        $optIn->save();

        $optIn->setRelatedRecords($related);

        return new OptInToken($optIn, $this->framework);
    }

    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(12));
        $prefix = 'cebb';

        return $prefix.'-'.substr($token, \strlen($prefix) + 1);
    }
}
