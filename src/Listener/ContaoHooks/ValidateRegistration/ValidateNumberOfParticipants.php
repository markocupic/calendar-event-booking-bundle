<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ValidateRegistration;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Message;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber\EventSubscriber;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook(ValidateNumberOfParticipants::HOOK, priority=ValidateNumberOfParticipants::PRIORITY)
 */
final class ValidateNumberOfParticipants extends AbstractHook
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
    public function __invoke(EventSubscriber $eventSubscriber, EventConfig $eventConfig): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        $form = $eventSubscriber->getForm();

        $numSeats = 1;

        if ($eventConfig->get('addEscortsToTotal') && $form->hasFormField('escorts')) {
            $objWidget = $form->getWidget('escorts');
            $numSeats += (int) $objWidget->value;
        }

        if (isset($_POST['addToWaitingListSubmit'])) {
            $valid = $this->bookingValidator->validateBookingMax($eventConfig, $numSeats, true);
        } else {
            $valid = $this->bookingValidator->validateBookingMax($eventConfig, $numSeats, false);
        }

        if (!$valid) {
            if ($this->bookingValidator->validateBookingMax($eventConfig, $numSeats, true)) {
                if (null !== ($form = $eventSubscriber->getForm())) {
                    $form->addSubmitFormField('addToWaitingListSubmit', $this->translator->trans('MSC.addToWaitingList', [], 'contao_default'));
                }
                $errorMsg = $this->translator->trans('MSC.notEnoughSeatsWaitingListPossible', [$eventConfig->get('maxMembers')], 'contao_default');
            } else {
                $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$eventConfig->get('maxMembers')], 'contao_default');
            }

            $this->message->addInfo($errorMsg);

            return false;
        }

        return true;
    }
}
