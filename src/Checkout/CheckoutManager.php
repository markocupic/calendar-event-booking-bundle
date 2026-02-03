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

namespace Markocupic\CalendarEventBookingBundle\Checkout;

use Markocupic\CalendarEventBookingBundle\Checkout\Step\CheckoutStepInterface;
use Markocupic\CalendarEventBookingBundle\Checkout\Step\ValidationCheckoutStepInterface;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Registry\PrioritizedServiceRegistryInterface;
use Symfony\Component\HttpFoundation\Request;

class CheckoutManager implements CheckoutManagerInterface
{
    public function __construct(
        private readonly PrioritizedServiceRegistryInterface $serviceRegistry,
    ) {
    }

    public function addCheckoutStep(CheckoutStepInterface $step, int $priority): void
    {
        $this->serviceRegistry->register($step->getIdentifier(), $priority, $step);
    }

    public function getSteps(): array
    {
        return array_map(static fn (CheckoutStepInterface $step) => $step->getIdentifier(), $this->serviceRegistry->all());
    }

    public function getStep(string $identifier): CheckoutStepInterface
    {
        /*
         * @var CheckoutStepInterface $step
         */
        return $this->serviceRegistry->get($identifier);
    }

    public function getNextStep(string $identifier): CheckoutStepInterface|null
    {
        return $this->serviceRegistry->getNextTo($identifier);
    }

    public function hasNextStep(string $identifier): bool
    {
        return $this->serviceRegistry->hasNextTo($identifier);
    }

    public function getPreviousStep(string $identifier): CheckoutStepInterface
    {
        return $this->serviceRegistry->getPreviousTo($identifier);
    }

    public function hasPreviousStep(string $identifier): bool
    {
        return $this->serviceRegistry->hasPreviousTo($identifier);
    }

    public function getPreviousSteps(string $identifier): array
    {
        return $this->serviceRegistry->getAllPreviousTo($identifier);
    }

    public function validateStep(CheckoutStepInterface $step, EventConfig $eventConfig, Request $request): bool
    {
        if ($step instanceof ValidationCheckoutStepInterface) {
            return $step->validate($eventConfig, $request);
        }

        return true;
    }

    public function prepareStep(CheckoutStepInterface $step, EventConfig $eventConfig, Request $request): array
    {
        return $step->prepareStep($eventConfig, $request);
    }

    public function getStepIndex(string $identifier): int
    {
        return $this->serviceRegistry->getIndex($identifier);
    }

    public function getFirstStep(): CheckoutStepInterface|null
    {
        $all = $this->serviceRegistry->all();
        if (empty($all)) {
            return null;
        }

        return $all[0];
    }

    public function commitStep(CheckoutStepInterface $step, EventConfig $eventConfig, Request $request): bool
    {
        return $step->commitStep($eventConfig, $request);
    }
}
