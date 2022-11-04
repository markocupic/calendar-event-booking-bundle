<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\ValidateRegistration;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Message;
use Doctrine\DBAL\Exception;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validate event subscription request
 * Check if event is bookable
 * Check if event has a bookable waiting list
 * Generate error messages
 * Add waiting list submit button, if available.
 *
 * @Hook(ValidateMaxEventMember::HOOK, priority=ValidateMaxEventMember::PRIORITY)
 */
final class ValidateMaxEventMember extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_VALIDATE_REGISTRATION;
    public const PRIORITY = 1100;

    private ContaoFramework $framework;
    private TranslatorInterface $translator;
    private BookingValidator $bookingValidator;

    // Adapters
    private Adapter $message;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator, BookingValidator $bookingValidator)
    {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->bookingValidator = $bookingValidator;

        // Adapters
        $this->message = $this->framework->getAdapter(Message::class);
    }

    /**
     * Important! return false will make the validation fail
     * Check if the number of available seats is not exceeded (consider the waiting list).
     *
     * @throws Exception
     */
    public function __invoke(EventRegistration $eventRegistration, EventConfig $eventConfig): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        $form = $eventRegistration->getForm();

        $arrMsg = [];
        $numSeats = 1;

        if ($eventConfig->get('addEscortsToTotal') && $form->hasFormField('escorts')) {
            $objWidget = $form->getWidget('escorts');
            $numSeats += (int) $objWidget->value;
        }

        if (isset($_POST['addToWaitingListSubmit'])) {
            if (!$eventConfig->hasWaitingList()) {
                $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorEventHasNoWaitingList', [], 'contao_default');
                $this->message->addError(implode(' ', $arrMsg));

                return false;
            }

            if ($this->bookingValidator->validateBookingMax($eventConfig, $numSeats)) {
                $arrMsg[] = $this->translator->trans('MSC.subscribeOnRegularListInsteadOnWaitingList', [$eventConfig->getNumberOfFreeSeats()], 'contao_default');
                $this->message->addError(implode(' ', $arrMsg));

                return false;
            }

            if (!$this->bookingValidator->validateBookingMaxWaitingList($eventConfig, $numSeats)) {
                // Unlimited seats available on the waiting list
                if (!$eventConfig->getWaitingListLimit()) {
                    return true;
                }

                if ($numSeats > 1) {
                    $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorWaitingListLimitExceededReduceNumEscorts', [$eventConfig->getNumberOfFreeSeats(true)], 'contao_default');
                    $this->message->addError(implode(' ', $arrMsg));

                    return false;
                }

                $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorWaitingListFull', [], 'contao_default');
                $this->message->addError(implode(' ', $arrMsg));

                return false;
            }

            // Subscription to the waiting list is possible!
            return true;
        }

        // EVENT SUBSCRIPTION REQUEST VALID && ACCEPTED, EVERYTHING FINE!!! This should be the normal use case.
        if ($this->bookingValidator->validateBookingMax($eventConfig, $numSeats)) {
            return true;
        }

        if (1 === $numSeats) {
            // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is fully booked, 1 seat required, no waiting list
            if (!$eventConfig->hasWaitingList()) {
                $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorEventFullyBooked', [], 'contao_default');
                $this->message->addError(implode(' ', $arrMsg));

                return false;
            }

            // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is fully booked, > 1 seats required, unlimited subscriptions to the waiting list possible
            if ($eventConfig->hasWaitingList() && !$eventConfig->getWaitingListLimit()) {
                $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorEventFullyBooked', [], 'contao_default');
                $arrMsg[] = $this->translator->trans('MSC.subscriptionInfoWaitingListPossible', [], 'contao_default');
                $this->message->addError(implode(' ', $arrMsg));

                $this->addToWaitingListSubmitButton($form);

                return false;
            }

            // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is fully booked, > 1 seats required, waiting list is full.
            if ($eventConfig->hasWaitingList() && 0 === $eventConfig->getNumberOfFreeSeats(true)) {
                $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorEventFullyBooked', [], 'contao_default');
                $this->message->addError(implode(' ', $arrMsg));

                return false;
            }

            // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is fully booked, 1 seat required, enough seats on the waiting list available
            $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorEventFullyBooked', [], 'contao_default');
            $arrMsg[] = $this->translator->trans('MSC.subscriptionInfoWaitingListPossibleXSeatsLeft', [$eventConfig->getNumberOfFreeSeats(true)], 'contao_default');
            $this->message->addError(implode(' ', $arrMsg));

            $this->addToWaitingListSubmitButton($form);

            return false;
        }// end if $numSeats === 1

        if ($numSeats > 1) {
            // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is fully booked
            if (0 === $eventConfig->getNumberOfFreeSeats()) {
                // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is fully booked, 1 seat required, waiting list is not available
                if (!$eventConfig->hasWaitingList()) {
                    $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorEventFullyBooked', [], 'contao_default');
                    $this->message->addError(implode(' ', $arrMsg));

                    return false;
                }

                // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is fully booked, > 1 seats required, unlimited subscriptions to the waiting list possible
                if ($eventConfig->hasWaitingList() && !$eventConfig->getWaitingListLimit()) {
                    $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorEventFullyBooked', [], 'contao_default');
                    $arrMsg[] = $this->translator->trans('MSC.subscriptionInfoWaitingListPossible', [], 'contao_default');
                    $this->message->addError(implode(' ', $arrMsg));

                    $this->addToWaitingListSubmitButton($form);

                    return false;
                }

                // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is fully booked, > 1 seats required, waiting list is full
                if (0 === $eventConfig->getNumberOfFreeSeats(true)) {
                    $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorEventFullyBooked', [], 'contao_default');
                    $this->message->addError(implode(' ', $arrMsg));

                    return false;
                }

                // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is fully booked, > 1 seats required, waiting list possible
                $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorNotEnoughFreeSeatsXSeatsLeft', [$eventConfig->getNumberOfFreeSeats()], 'contao_default');
                $arrMsg[] = $this->translator->trans('MSC.subscriptionInfoWaitingListPossibleXSeatsLeft', [$eventConfig->getNumberOfFreeSeats(true)], 'contao_default');
                $this->message->addError(implode(' ', $arrMsg));

                $this->addToWaitingListSubmitButton($form);

                return false;
            }// end if 0 === $eventConfig->getNumberOfFreeSeats()

            if ($eventConfig->getNumberOfFreeSeats() > 0) {
                // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is not fully booked, $numSeats exceeds number of free seats, > 1 seats required, waiting list not available
                if (!$eventConfig->hasWaitingList()) {
                    $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorNotEnoughFreeSeatsXSeatsLeft', [$eventConfig->getNumberOfFreeSeats()], 'contao_default');
                    $this->message->addError(implode(' ', $arrMsg));

                    return false;
                }

                // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is not fully booked, $numSeats exceeds number of free seats, > 1 seats required, unlimited subscriptions to the waiting list possible
                if ($eventConfig->hasWaitingList() && !$eventConfig->getWaitingListLimit()) {
                    $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorNotEnoughFreeSeatsXSeatsLeft', [$eventConfig->getNumberOfFreeSeats()], 'contao_default');
                    $arrMsg[] = $this->translator->trans('MSC.subscriptionInfoWaitingListPossible', [], 'contao_default');
                    $this->message->addError(implode(' ', $arrMsg));

                    $this->addToWaitingListSubmitButton($form);

                    return false;
                }

                // EVENT SUBSCRIPTION REQUEST NOT VALID: Event is not fully booked, $numSeats exceeds number of free seats, > 1 seats required, waiting list possible
                $arrMsg[] = $this->translator->trans('MSC.subscriptionErrorNotEnoughFreeSeatsXSeatsLeft', [$eventConfig->getNumberOfFreeSeats()], 'contao_default');
                $arrMsg[] = $this->translator->trans('MSC.subscriptionInfoWaitingListPossibleXSeatsLeft', [$eventConfig->getNumberOfFreeSeats(true)], 'contao_default');
                $this->message->addError(implode(' ', $arrMsg));

                $this->addToWaitingListSubmitButton($form);

                return false;
            } // end if $eventConfig->getNumberOfFreeSeats() > 0

            throw new \LogicException('Event subscription error. Please contact your website administrator.');
        } // end if $numSeats > 1

        throw new \LogicException('Event subscription error. Please contact your website administrator.');
    }

    private function addToWaitingListSubmitButton(Form $form): void
    {
        if (!$form->hasFormField('addToWaitingListSubmit')) {
            $form->addSubmitFormField(
                'addToWaitingListSubmit',
                $this->translator->trans(
                    'MSC.addToWaitingListBtnLbl',
                    [],
                    'contao_default'
                )
            );

            $form->getWidget('addToWaitingListSubmit')->value = 'value';
        }
    }
}