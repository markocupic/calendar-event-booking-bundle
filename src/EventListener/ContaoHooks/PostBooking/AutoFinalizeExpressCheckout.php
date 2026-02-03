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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\PostBooking;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\FrontendUser;
use Contao\Model\Collection;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Event\PostBookingEvent;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CebbOrderModel;
use Markocupic\CalendarEventBookingBundle\Model\CebbRegistrationModel;
use Markocupic\CalendarEventBookingBundle\Storage\SessionStorage;
use Markocupic\CalendarEventBookingBundle\Util\CartUtil;
use Markocupic\CalendarEventBookingBundle\Util\CheckoutUtil;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Auto-finalize the express checkout after a participant has been registered.
 *
 * This hook listener handles the complete finalization of express checkout bookings:
 *
 * Triggered after: A participant has been successfully registered via the booking form
 *
 * Performs:
 * - Creates a new order entry with unique UUID
 * - Marks all registrations as completed (checkoutCompleted = 1)
 * - Associates registrations with the created order
 * - Sets confirmed date for confirmed bookings
 * - Marks cart as completed
 * - Dispatches PostBookingEvent to trigger confirmation emails and notifications
 * - Stores order ID in session for the confirmation page redirect
 *
 * Benefits:
 * - Users experience a seamless one-step booking process
 * - Confirmation emails are sent immediately after registration
 * - Order data is properly recorded in the database
 * - Automatic redirect to confirmation page via doAutoForward
 *
 * @author Kirsten Roschanski <support@inszenium.de>
 * @see ExpressCheckoutStep
 * @see PostBookingEvent
 */
#[AsHook('calEvtBookingPostBooking', priority: 100)]
final class AutoFinalizeExpressCheckout extends AbstractHook
{
    public function __construct(
        private readonly CartUtil $cartUtil,
        private readonly CheckoutUtil $checkoutUtil,
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    /**
     * Execute the auto-finalization hook after a participant is registered.
     *
     * This is the main hook entry point. It:
     * - Checks if express checkout is enabled
     * - Verifies the current module is using express checkout type
     * - Triggers the single-step finalization process
     * - Handles any errors gracefully without breaking the registration
     *
     * @param array<string, mixed> $arrRegistration The newly created registration data
     * @param array<string, mixed> $arrFormData     The submitted form data
     *
     * @return void
     */
    public function __invoke(array $arrRegistration, array $arrFormData): void
    {
        $this->logger->debug('AutoFinalizeExpressCheckout Hook CALLED');
        
        if (!$this->isEnabled()) {
            $this->logger->debug('AutoFinalizeExpressCheckout Hook - NOT ENABLED');
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            $this->logger->debug('AutoFinalizeExpressCheckout Hook - NO REQUEST');
            return;
        }

        // Check if we're using the express checkout type
        try {
            $moduleModel = $this->checkoutUtil->getModuleModel($request);
            $checkoutType = $moduleModel->cebb_checkoutType;
            $this->logger->debug('AutoFinalizeExpressCheckout Hook - Checkout type: ' . $checkoutType);
            
            if ('express' !== $checkoutType) {
                $this->logger->debug('AutoFinalizeExpressCheckout Hook - Not express checkout, returning');
                return;
            }
        } catch (\Exception $e) {
            $this->logger->error('AutoFinalizeExpressCheckout Hook - Error getting module: ' . $e->getMessage());
            return;
        }

        // Finalize the entire booking immediately after registration
        try {
            $this->finalizeSingleStepCheckout($request);
            $this->logger->debug('Express Checkout Hook - Auto-finalization completed successfully');
        } catch (\Exception $e) {
            // Log error but don't break the registration
            $this->logger->error('Express checkout auto-finalization error: ' . $e->getMessage());
        }
    }

    /**
     * Complete the express checkout finalization process.
     *
     * Performs all necessary actions to finalize the booking immediately after
     * registration:
     * - Creates a new order entity with UUID
     * - Marks all registrations as checkout completed
     * - Associates registrations with the created order
     * - Sets confirmed dates for confirmed bookings
     * - Marks cart as completed
     * - Dispatches PostBookingEvent to trigger confirmation emails
     * - Stores redirect URL in session for confirmation page redirect
     *
     * All operations are wrapped in a database transaction to ensure consistency.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The HTTP request
     *
     * @return void
     *
     * @throws \Exception If database operations fail (transaction is rolled back)
     */
    private function finalizeSingleStepCheckout($request): void
    {
        $cart = $this->cartUtil->getCart($request);
        $arrRegistrations = $this->cartUtil->getRegistrations($request);
        $eventConfig = $this->checkoutUtil->getEventConfig($request);
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

            // Finalize each registration
            foreach ($arrRegistrations as $arrRegistration) {
                $registration = CebbRegistrationModel::findOneByUuid($arrRegistration['uuid']);

                if (null === $registration) {
                    throw new \Exception('Could not find registration with UUID: ' . $arrRegistration['uuid']);
                }

                $registration->checkoutCompleted = true;
                $registration->orderUuid = $order->uuid;
                $registration->tstamp = time();
                $registration->dateAdded = time();

                // Set confirmed date if status is confirmed
                if ('confirmed' === $registration->bookingState) {
                    $registration->confirmedOn = time();
                }

                $registration->save();
                $regModels[] = $registration;
            }

            // Mark cart as completed
            $cart->checkoutCompleted = true;
            $cart->tstamp = time();
            $cart->save();

            // Dispatch the PostBookingEvent (this sends confirmation emails, etc.)
            $event = new PostBookingEvent(
                $eventConfig,
                $order,
                $cart,
                new Collection($regModels, CebbRegistrationModel::getTable()),
                $request
            );
            $this->eventDispatcher->dispatch($event);

            $this->connection->commit();

            $this->logger->debug('Express Checkout Hook - Auto-finalization completed successfully. Order ID: ' . $order->id);
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $cart->refresh();
            $this->logger->error('Express Checkout finalization error: ' . $e->getMessage());
            throw $e;
        }
    }
}


