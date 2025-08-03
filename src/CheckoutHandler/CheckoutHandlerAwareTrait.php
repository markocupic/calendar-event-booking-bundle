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

trait CheckoutHandlerAwareTrait
{
    protected CheckoutHandlerInterface|null $checkoutHandler = null;

    public function setCheckoutHandler(iterable $checkoutHandlers, string $checkoutHandler): void
    {
        $checkoutHandlers = iterator_to_array($checkoutHandlers);

        /** @var CheckoutHandlerInterface $checkoutHandler */
        foreach ($checkoutHandlers as $handler) {
            if ($checkoutHandler === $handler->getIdentifier()) {
                $this->checkoutHandler = $handler;
                break;
            }
        }
    }

    public function getCheckoutHandler(): CheckoutHandlerInterface|null
    {
        return $this->checkoutHandler;
    }
}
