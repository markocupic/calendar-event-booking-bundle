<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\ProcessFormData;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Form;
use Contao\FrontendUser;
use Contao\System;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingController;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingType;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Security;

#[AsHook(AddEventSubscription::HOOK, priority: 1000)]
final class AddEventSubscription extends AbstractHook
{
    public const HOOK = 'processFormData';

    private Adapter $system;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly EventFactory $eventFactory,
        private readonly Security $security,
        private readonly EventRegistration $eventRegistration,
    ) {
        $this->system = $this->framework->getAdapter(System::class);
    }

    public function __invoke(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!self::isEnabled()) {
            return;
        }

        if (null === ($event = EventConfig::getEventFromRequest())) {
            return;
        }

        $eventConfig = $this->eventFactory->create($event);

        $registration = new CalendarEventsMemberModel();
        $registration->pid = $eventConfig->getModel()->id;
        $registration->tstamp = time();
        $registration->dateAdded = time();
        $registration->bookingState = EventBookingController::CASE_WAITING_LIST_POSSIBLE === $eventConfig->getEventStatus() ? BookingState::STATE_WAITING_LIST : $eventConfig->get('bookingState');
        $registration->bookingToken = Uuid::uuid4()->toString();
        $registration->bookingType = $this->security->getUser() instanceof FrontendUser ? BookingType::TYPE_MEMBER : BookingType::TYPE_GUEST;
        $registration->formData = json_encode($submittedData);

        foreach ($submittedData as $strFieldName => $varValue) {
            $registration->{$strFieldName} = $varValue;
        }

        $this->eventRegistration->setModel($registration);

        // Trigger pre-booking hook: add your custom code here.
        $formDetails = ['submittedData' => $submittedData, 'formData' => $formData, 'files' => $files, 'labels' => $labels, 'form' => $form];

        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_BOOKING]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_BOOKING])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_BOOKING] as $callback) {
                $this->system->importStatic($callback[0])->{$callback[1]}($eventConfig, $this->eventRegistration, $formDetails);
            }
        }

        // Save to Database
        $registration->save();

        // Trigger post-booking hook: add data to the session, send notifications, log things, etc.
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_POST_BOOKING]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_POST_BOOKING])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_POST_BOOKING] as $callback) {
                $this->system->importStatic($callback[0])->{$callback[1]}($eventConfig, $this->eventRegistration, $formDetails);
            }
        }
    }
}
