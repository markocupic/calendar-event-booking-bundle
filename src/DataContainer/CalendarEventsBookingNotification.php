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

use Contao\CoreBundle\DataContainer\RecordLabel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;

class CalendarEventsBookingNotification
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    #[AsCallback(table: 'tl_calendar_events_booking_notification', target: 'list.label.label')]
    /**
     * Add an image to each record.
     *
     * @param array  $row
     * @param string $label
     * @param array  $args
     *
     * @return array|\Stringable RecordLabel on Contao 6
     */
    public function addIcon($row, $label, DataContainer $dc, $args)
    {
        $iconSuccess = 'bundles/markocupiccalendareventbooking/icons/check-circle.svg';
        $iconFail = 'bundles/markocupiccalendareventbooking/icons/alert-triangle.svg';

        if ($row['delivered']) {
            $icon = $iconSuccess;
            $status = 'success';
        } else {
            $icon = $iconFail;
            $status = 'error';
        }

        $args[0] = \sprintf(
            '<div class="list_icon_delivered" data-delivered="%s"><img src="%s" style="width:16px"/></div>',
            $status,
            StringUtil::specialcharsAttribute($this->framework->getAdapter(Image::class)->getUrl($icon)),
        );

        // Contao 6 HTML-encodes plain label callback return values. Pass the columns
        // as RecordLabel, otherwise the icon markup would be displayed as text.
        if (class_exists(RecordLabel::class)) {
            return RecordLabel::fromHtml(array_values($args));
        }

        return $args;
    }
}
