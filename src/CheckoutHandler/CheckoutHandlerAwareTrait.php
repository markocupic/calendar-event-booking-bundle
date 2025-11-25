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

namespace Markocupic\CalendarEventBookingBundle\CheckoutHandler;

use Psr\Container\ContainerInterface;

trait CheckoutHandlerAwareTrait
{
    protected CheckoutHandlerInterface|null $checkoutHandler = null;

    public function setCheckoutHandler(CheckoutHandlerInterface $checkoutHandler): void
    {
        $this->checkoutHandler = $checkoutHandler;
    }

    public function getCheckoutHandler(): CheckoutHandlerInterface|null
    {
        return $this->checkoutHandler;
    }

    public function getTypes(ContainerInterface $checkoutHandlers): array
    {
        return array_map(static fn (string $type): string => $type, array_keys($checkoutHandlers->getProvidedServices()));
    }

    public function resolveCheckoutHandler(ContainerInterface $checkoutHandlers, string $checkoutHandler): CheckoutHandlerInterface
    {
        if ($checkoutHandlers->has($checkoutHandler)) {
            return $checkoutHandlers->get($checkoutHandler);
        }

        throw new \Exception(\sprintf('Could not find a checkout handler of type "%s".', $checkoutHandler));
    }
}
