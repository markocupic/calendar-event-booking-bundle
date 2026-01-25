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

use Symfony\Component\HttpFoundation\Response;

class CheckoutResult
{
    public function __construct(
        private string $checkoutType,
        private array $data = [],
        private Response|null $response = null,
    ) {
    }

    public function getCheckoutType(): string
    {
        return $this->checkoutType;
    }

    public function setCheckoutType(string $checkoutType): void
    {
        $this->checkoutType = $checkoutType;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function hasResponse(): bool
    {
        return null !== $this->response;
    }

    public function getResponse(): Response|null
    {
        return $this->response;
    }

    public function setResponse(Response|null $response): void
    {
        $this->response = $response;
    }
}
