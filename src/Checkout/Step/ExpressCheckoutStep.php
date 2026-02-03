<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * Contributions by Kirsten Roschanski <support@inszenium.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Checkout\Step;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\FormModel;
use Contao\FrontendUser;
use Contao\Message;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Codefog\HasteBundle\Form\Form;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Markocupic\CalendarEventBookingBundle\Checkout\Exception\CheckoutException;
use Markocupic\CalendarEventBookingBundle\Event\CreateFinalizeStepFormEvent;
use Markocupic\CalendarEventBookingBundle\Event\PostBookingEvent;
use Markocupic\CalendarEventBookingBundle\Event\SetBookingAvailabilityEvent;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;
use Markocupic\CalendarEventBookingBundle\Model\CebbCartModel;
use Markocupic\CalendarEventBookingBundle\Model\CebbRegistrationModel;
use Markocupic\CalendarEventBookingBundle\Storage\SessionStorage;
use Markocupic\CalendarEventBookingBundle\Util\CartUtil;
use Markocupic\CalendarEventBookingBundle\Util\CheckoutUtil;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Express checkout step - combines subscription, overview and finalization in one single step.
 *
 * This step simplifies the booking process by combining all steps into a single page:
 * - Users can add participants to the cart
 * - Review the registration details
 * - Complete the booking with automatic finalization and redirect to confirmation page
 *
 * After a participant is registered, the AutoFinalizeExpressCheckout hook automatically:
 * - Creates an order
 * - Marks registrations as completed
 * - Dispatches PostBookingEvent (sends confirmation emails)
 * - Redirects to the confirmation page
 *
 * @author Kirsten Roschanski <support@inszenium.de>
 * @see AutoFinalizeExpressCheckout
 */
class ExpressCheckoutStep implements CheckoutStepInterface, ValidationCheckoutStepInterface
{
    public const CASE_BOOKING_NOT_YET_POSSIBLE = 'bookingNotYetPossible';
    public const CASE_BOOKING_NO_LONGER_POSSIBLE = 'bookingNoLongerPossible';
    public const CASE_BOOKING_POSSIBLE = 'bookingPossible';
    public const CASE_EVENT_FULLY_BOOKED = 'eventFullyBooked';
    public const CASE_EVENT_NOT_BOOKABLE = 'eventNotBookable';
    public const CASE_WAITING_LIST_POSSIBLE = 'waitingListPossible';

    private const STEP_IDENTIFIER = 'express_checkout';

    private string $templatePath = '';

    private Form|null $finalizeForm = null;

    private readonly Adapter $cebbRegistrationModelAdapter;
    private readonly Adapter $messageAdapter;
    private readonly Adapter $orderAdapter;
    private readonly Adapter $pageModelAdapter;
    private readonly Adapter $stringUtilAdapter;

    public function __construct(
        private readonly BookingValidator $bookingValidator,
        private readonly CartUtil $cartUtil,
        private readonly CheckoutUtil $checkoutUtil,
        private readonly Connection $connection,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventRegistration $eventRegistration,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {
        $this->cebbRegistrationModelAdapter = $this->framework->getAdapter(CebbRegistrationModel::class);
        $this->messageAdapter = $this->framework->getAdapter(Message::class);
        $this->orderAdapter = $this->framework->getAdapter(CebbOrderModel::class);
        $this->pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $this->stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
    }

    /**
     * Initialize the express checkout step and create the finalization form.
     *
     * This method is called at the start of the checkout process. It prepares
     * the express checkout by creating the form that participants will submit
     * to finalize their booking.
     *
     * @param EventConfig $eventConfig The event configuration
     * @param Request     $request     The HTTP request
     *
     * @return void
     */
    public function initialize(EventConfig $eventConfig, Request $request): void
    {
        $cart = $this->cartUtil->hasCart($request) ? $this->cartUtil->getCart($request) : null;

        if ($cart instanceof CebbCartModel) {
            $this->finalizeForm = $this->createFinalizeForm($request, $eventConfig, $cart);
        }
    }

    /**
     * Get the unique identifier for this checkout step.
     *
     * @return string The step identifier ('express_checkout')
     */
    public function getIdentifier(): string
    {
        return self::STEP_IDENTIFIER;
    }

    /**
     * Get the Twig template path for rendering the express checkout step.
     *
     * @return string The template path
     */
    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * Set the Twig template path for rendering the express checkout step.
     *
     * @param string $templatePath The template path to use
     *
     * @return void
     */
    public function setTemplatePath(string $templatePath = ''): void
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Determine if the checkout process should automatically advance to the next step.
     *
     * Returns true after the AutoFinalizeExpressCheckout hook has created an order,
     * In express checkout, we don't auto-forward to another step since this IS the last step.
     * Instead, commitStep() will complete and getResponse() will be called.
     *
     * @param EventConfig $eventConfig The event configuration
     * @param Request     $request     The HTTP request
     *
     * @return bool Always false - no auto-forward needed
     */
    public function doAutoForward(EventConfig $eventConfig, Request $request): bool
    {
        // Express checkout doesn't auto-forward to another step
        // Instead, the controller calls getResponse() after commitStep()
        return false;
    }

    /**
     * Validate if the express checkout step can be executed.
     *
     * Checks that:
     * - The event is still bookable
     * - A cart exists with pending registrations
     * - The checkout has not already been completed
     *
     * @param EventConfig $eventConfig The event configuration
     * @param Request     $request     The HTTP request
     *
     * @return bool True if the step can be executed
     */
    public function validate(EventConfig $eventConfig, Request $request): bool
    {
        if (!$eventConfig->isBookable()) {
            return false;
        }

        $cart = $this->cartUtil->hasCart($request) ? $this->cartUtil->getCart($request) : null;

        if (null === $cart) {
            return false;
        }

        if ($cart->checkoutCompleted) {
            return false;
        }

        return true;
    }

    /**
     * Commit the express checkout step.
     *
     * In express checkout mode, the booking is finalized automatically by the processFormData hook
     * (CaptureOrder) which stores the ORDER_ID in the session.
     *
     * @param EventConfig $eventConfig The event configuration
     * @param Request     $request     The HTTP request
     *
     * @return bool True if finalization has occurred and checkout is complete
     */
    public function commitStep(EventConfig $eventConfig, Request $request): bool
    {
        $sessionStorage = new SessionStorage($request);
        $bag = $sessionStorage->getData();

        // If an order was created (by the hook), we're done
        if (!empty($bag[SessionStorage::ORDER_ID])) {
            return true;
        }

        return false;
    }

    /**
     * Prepare template data for rendering the express checkout step.
     *
     * Gathers all necessary data for the template, including:
     * - The booking form for participant registration
     * - Event availability status and explanation
     * - Available seats information
     * - CSRF token for form security
     *
     * @param EventConfig $eventConfig The event configuration
     * @param Request     $request     The HTTP request
     *
     * @return array<string, mixed> Template data for rendering
     *
     * @throws \RuntimeException If no booking form is assigned to the module
     */
    public function prepareStep(EventConfig $eventConfig, Request $request): array
    {
        $template = [];

        $this->framework->getAdapter(System::class)->loadLanguageFile($this->eventRegistration->getTable());
        $moduleModel = $this->checkoutUtil->getModuleModel($request);
        $form = $this->framework->getAdapter(FormModel::class)->findById($moduleModel->form);
        $cart = $this->cartUtil->hasCart($request) ? $this->cartUtil->getCart($request) : null;

        if (null === $form) {
            throw new \RuntimeException('No event booking form assigned to the frontend module. Please check if a booking form has been selected in frontend module with ID: '.$moduleModel->id);
        }

        // Get booking availability
        $intendedSeats = 1;
        $bookingAvailability = $eventConfig->getEventStatus($intendedSeats);
        $bookingAvailabilityExplain = $this->getBookingAvailabilityExplain($eventConfig, $bookingAvailability);

        $event = new SetBookingAvailabilityEvent($request, $eventConfig, $bookingAvailability, $bookingAvailabilityExplain);
        $this->eventDispatcher->dispatch($event);

        if ($event->isStopped()) {
            $bookingAvailability = $event->getBookingAvailability();
            $bookingAvailabilityExplain = $event->getBookingAvailabilityExplain();
        }

        $template['bookingAvailability'] = $bookingAvailability;
        $template['bookingAvailabilityExplain'] = $bookingAvailabilityExplain;

        // Always set the booking form so users can add participants
        $this->eventRegistration->setModuleData($moduleModel->row());
        $template['bookingForm'] = $this->framework->getAdapter(Controller::class)->getForm($moduleModel->form);

        $template['csrf_token'] = $this->csrfTokenManager->getDefaultTokenValue();
        $template['model'] = $moduleModel;

        return $template;
    }

    /**
     * Finalize the single-step checkout by creating the order and marking registrations complete.
     *
     * This internal method handles:
     * - Form validation
     * - Order entity creation with UUID
     * - Registration finalization and association with order
     * - Cart completion marking
     * - PostBookingEvent dispatch for sending confirmation emails
     *
     * @param EventConfig $eventConfig The event configuration
     * @param Request     $request     The HTTP request
     *
     * @return bool True if finalization was successful
     */
    private function finalizeSingleStepCheckout(EventConfig $eventConfig, Request $request): bool
    {
        if (!$this->finalizeForm->validate()) {
            return false;
        }

        $cart = $this->cartUtil->getCart($request);
        $arrRegistrations = $this->stringUtilAdapter->deserialize($cart->registrations, true);
        $regModels = [];

        $this->connection->beginTransaction();

        try {
            // Create the order entity
            $order = new CebbOrderModel();
            $order->eventId = $eventConfig->get('id');
            $order->dateAdded = time();
            $order->tstamp = time();
            $order->uuid = Uuid::uuid4()->toString();

            $user = $this->security->getUser();

            if ($user instanceof FrontendUser) {
                $order->memberId = $user->id;
            }

            $order->save();

            foreach ($arrRegistrations as $arrRegistration) {
                $regModels[] = $this->finalizeRegistration($arrRegistration, $order);
            }

            $cart->checkoutCompleted = true;
            $cart->tstamp = time();
            $cart->save();

            // Dispatch the PostBookingEvent
            $event = new PostBookingEvent($eventConfig, $order, $cart, new Collection($regModels, CebbRegistrationModel::getTable()), $request);
            $this->eventDispatcher->dispatch($event);
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $cart->refresh();

            if ($e instanceof CheckoutException) {
                $this->messageAdapter->addError($e->getTranslatableText());
            } else {
                $this->messageAdapter->addError($this->translator->trans('ERR.text_booking_request_failed_due_to_unexpected_error', [], 'contao_default'));
            }

            return false;
        }

        $sessionStorage = new SessionStorage($request);
        $bag = $sessionStorage->getData();
        $bag[SessionStorage::ORDER_ID] = $order->id;
        $sessionStorage->storeData($bag);

        return true;
    }

    /**
     * Create the form for express checkout finalization.
     *
     * Generates a simple form with just a submit button. This form serves as
     * the confirmation step before registrations are finalized. Custom modifications
     * can be made via the CreateFinalizeStepFormEvent.
     *
     * @param Request        $request     The HTTP request
     * @param EventConfig    $eventConfig The event configuration
     * @param CebbCartModel  $cart        The shopping cart with registrations
     *
     * @return Form The finalization form
     */
    protected function createFinalizeForm(Request $request, EventConfig $eventConfig, CebbCartModel $cart): Form
    {
        $form = new Form('cebb_express_finalize', 'POST');

        $form->addSubmitFormField(
            $this->translator->trans('BTN.cebb_finalize_submit_lbl', [], 'contao_default'),
            'cebb_express_finalize:submit_btn'
        );

        // Allow custom modifications via event
        $event = new CreateFinalizeStepFormEvent($form, $request, $eventConfig, $cart);
        $this->eventDispatcher->dispatch($event);

        return $form;
    }

    /**
     * Mark a registration as finalized and complete.
     *
     * Updates the registration record to reflect checkout completion:
     * - Marks checkoutCompleted as true
     * - Associates with the order UUID
     * - Sets confirmation date if booking was confirmed
     * - Updates modification timestamp
     *
     * @param array            $arrRegistration The registration data array with 'uuid' key
     * @param CebbOrderModel   $order           The created order model
     *
     * @return CebbRegistrationModel The updated registration model
     *
     * @throws CheckoutException If registration cannot be found
     */
    protected function finalizeRegistration(array $arrRegistration, CebbOrderModel $order): CebbRegistrationModel
    {
        $registration = $this->cebbRegistrationModelAdapter->findOneByUuid($arrRegistration['uuid']);

        if (null === $registration) {
            throw new CheckoutException('Could not find your registration.', $this->translator->trans('ERR.cebb_checkout_exception::registration_not_found', [], 'contao_default'));
        }

        $registration->checkoutCompleted = true;
        $registration->orderUuid = $order->uuid;
        $registration->tstamp = time();
        $registration->dateAdded = time();

        if (BookingState::STATE_CONFIRMED === $registration->bookingState) {
            $registration->confirmedOn = time();
        }

        $registration->save();

        return $registration;
    }

    /**
     * Delete a registration from cart and database.
     *
     * Removes a registration by UUID from both the database and the session cart.
     * This is used when users remove participants before finalizing checkout.
     *
     * @param string  $uuid    The registration UUID
     * @param Request $request The HTTP request
     *
     * @return void
     */
    protected function deleteRegistration(string $uuid, Request $request): void
    {
        // Delete from tl_cebb_registration
        $this->connection->delete('tl_cebb_registration', ['uuid' => $uuid], ['uuid' => Types::STRING]);

        $arrRegistrations = $this->cartUtil->getRegistrations($request);

        // Delete from tl_cebb_cart
        foreach ($arrRegistrations as $index => $registration) {
            if ($uuid === $registration['uuid']) {
                unset($arrRegistrations[$index]);
            }
        }

        // Update registrations in the allocated cart record
        $cart = $this->cartUtil->getCart($request);
        $cart->registrations = serialize($arrRegistrations);
        $cart->tstamp = time();
        $cart->save();
    }

    /**
     * Get a translated explanation message for the current booking availability status.
     *
     * Returns localized text that explains why booking is or isn't possible:
     * - When booking opens
     * - Why booking has closed
     * - If event is fully booked
     * - If waiting list is available
     * - If booking is currently possible
     *
     * @param EventConfig $eventConfig       The event configuration
     * @param string      $bookingAvailability The availability status constant
     *
     * @return string Translated explanation message
     */
    protected function getBookingAvailabilityExplain(EventConfig $eventConfig, string $bookingAvailability): string
    {
        $text = '';

        switch ($bookingAvailability) {
            case self::CASE_BOOKING_NOT_YET_POSSIBLE:
                $text = $this->translator->trans(
                    'MSC.'.$bookingAvailability,
                    [$this->framework->getAdapter(Date::class)->parse($this->framework->getAdapter(Config::class)->get('dateFormat'), $eventConfig->get('bookingStartDate'))],
                    'contao_default',
                );
                break;
            case self::CASE_BOOKING_NO_LONGER_POSSIBLE:
            case self::CASE_EVENT_FULLY_BOOKED:
            case self::CASE_WAITING_LIST_POSSIBLE:
            case self::CASE_BOOKING_POSSIBLE:
                $text = $this->translator->trans(
                    'MSC.'.$bookingAvailability,
                    [],
                    'contao_default',
                );
                break;
        }

        return $text;
    }
}
