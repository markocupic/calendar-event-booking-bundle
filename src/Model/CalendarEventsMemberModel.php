<?php

declare(strict_types=1);

/*
 * This file is part of markocupic/calendar-event-booking-bundle.
 *
 * (c) Marko Cupic 2020 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Model;

use Contao\Model;

/**
 * Class CalendarEventsMemberModel.
 */
class CalendarEventsMemberModel extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_calendar_events_member';
}
