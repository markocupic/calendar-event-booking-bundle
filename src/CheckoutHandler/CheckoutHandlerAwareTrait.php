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

use Psr\Container\ContainerInterface;

trait CheckoutHandlerAwareTrait
{
    protected CheckoutHandlerInterface|null $checkoutHandler = null;

    public function setCheckoutHandler(ContainerInterface $checkoutHandlers, string $checkoutHandler): void
    {
        if ($checkoutHandlers->has($checkoutHandler)) {
            $this->checkoutHandler = $checkoutHandlers->get($checkoutHandler);

            return;
        }

        throw new \Exception(\sprintf('Could not find a checkout handler of type "%s".', $checkoutHandler));
    }

    public function getCheckoutHandler(): CheckoutHandlerInterface|null
    {
        return $this->checkoutHandler;
    }
}
