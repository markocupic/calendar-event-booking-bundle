<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\CheckoutHandler;

use Contao\ModuleModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('cebb.checkout_handler')]
class DefaultCheckoutHandler implements CheckoutHandlerInterface
{
    public const NAME = 'default';

    public const TEMPLATE_NAME = '@MarkocupicCalendarEventBooking/Checkout/default.html.twig';

    public function getIdentifier(): string
    {
        return self::NAME;
    }

    public function getCheckoutData(CalendarEventsMemberModel $booking, ModuleModel $model, Request $request): CheckoutData
    {
        $template = [];
        $template['booking'] = $booking->row();
        $template['event'] = $booking->getRelated('pid')?->row();
        $template['module'] = $model;

        return new CheckoutData($this->getIdentifier(), self::TEMPLATE_NAME, $template);
    }
}
