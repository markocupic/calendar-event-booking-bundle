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

namespace Markocupic\CalendarEventBookingBundle\Checkout;

use Contao\ModuleModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('cebb.checkout_handler')]
class DefaultCheckoutHandler implements CheckoutHandlerInterface
{
    public const string NAME = 'default';

    public static function getType(): string
    {
        return self::NAME;
    }

    public function handleRequest(CalendarEventsMemberModel $bookingModel, ModuleModel $moduleModel, Request $request): CheckoutResult
    {
        $event = $bookingModel->getRelated('pid');
        $calendar = $event?->getRelated('pid');

        if (null === $event) {
            throw new \Exception('Event not found.');
        }

        if (null === $calendar) {
            throw new \Exception('Calendar not found.');
        }

        if (!$calendar->allowEventBooking) {
            throw new \Exception('Event booking not allowed for this calendar.');
        }

        $data = [];
        $data['booking'] = $bookingModel->row();
        $data['event'] = $event->row();
        $data['calendar'] = $calendar->row();
        $data['module'] = $moduleModel;

        return new CheckoutResult($this->getType(), $data);
    }
}
