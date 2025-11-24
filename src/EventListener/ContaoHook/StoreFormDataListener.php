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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\FrontendUser;
use Markocupic\CalendarEventBookingBundle\CheckoutHandler\CheckoutHandlerAwareTrait;
use Markocupic\CalendarEventBookingBundle\CheckoutHandler\PaymentCheckoutHandlerInterface;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingFormController;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsHook(self::HOOK, priority: 1000)]
class StoreFormDataListener
{
    use CheckoutHandlerAwareTrait;

    public const HOOK = 'storeFormData';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
        #[AutowireLocator('cebb.checkout_handler', defaultIndexMethod: 'getType')]
        private readonly ContainerInterface $checkoutHandlers,
    ) {
    }

    public function __invoke(array $data, Form $form): array
    {
        if (!$form->isCalendarEventBookingForm) {
            return $data;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request->attributes->has('_event_booking_form_module')) {
            return $data;
        }

        /** @var EventBookingFormController $bookingModuleInstance */
        $bookingModuleInstance = $request->attributes->get('_event_booking_form_module');

        $event = $bookingModuleInstance->getEvent();

        if (null === $event) {
            throw new \Exception('Event not found.');
        }

        $calendar = $bookingModuleInstance->getCalendar();

        if (null === $calendar) {
            throw new \Exception('Calendar not found.');
        }

        // Set the redirect checkout page
        if (!$form->getModel()->jumpTo) {
            $form->getModel()->jumpTo = $calendar->eventBookingCheckoutPage;
        }

        if (null === $calendar->eventBookingCheckoutPage) {
            throw new \Exception('Event booking checkout handler not found.');
        }

        $checkoutHandler = $this->resolveCheckoutHandler($this->checkoutHandlers, $calendar->eventBookingCheckoutHandler);

        $data['formSubmit'] = json_encode($data);
        $data['pid'] = $event->id;
        $data['tstamp'] = time();
        $data['addedOn'] = time();
        $data['bookingToken'] = Uuid::uuid4()->toString();
        $data['waitingList'] = $bookingModuleInstance->waitingListOpen ? 1 : 0;
        $data['member'] = 0;
        $data['form'] = $form->id;
        $data['ticketAmount'] = (int) ($data['ticketAmount'] ?? 1);
        $data['escorts'] = (int) ($data['escorts'] ?? 0);
        $data['temporaryReserved'] = $calendar->requireOptIn ? 1 : 0;
        $data['checkoutHandler'] = $checkoutHandler->getType();

        if ($checkoutHandler instanceof PaymentCheckoutHandlerInterface) {
            $data['temporaryReserved'] = 1;
            $data['paid'] = 0;
        }

        // Attach the booking token to the request
        $request->attributes->set('_calendar_event_booking_token', $data['bookingToken']);

        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof FrontendUser) {
            return $data;
        }

        // Also store the member ID who submitted the form
        $data['member'] = $user->id;

        return $data;
    }
}
