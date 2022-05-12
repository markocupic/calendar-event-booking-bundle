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

namespace Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FormModel;
use Contao\FrontendUser;
use Contao\System;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingType;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

final class EventSubscriber
{
    public const TABLE = 'tl_calendar_events_member';

    private ContaoFramework $framework;
    private RequestStack $requestStack;
    private Security $security;

    private ?Form $form = null;
    private ?CalendarEventsMemberModel $model = null;
    private array $moduleData = [];

    // Adapter
    private Adapter $systemAdapter;
    private Adapter $calendarEventsMemberAdapter;

    public function __construct(ContaoFramework $framework, RequestStack $requestStack, Security $security)
    {
        $this->framework = $framework;
        $this->requestStack = $requestStack;
        $this->security = $security;

        // Adapters
        $this->systemAdapter = $this->framework->getAdapter(System::class);
        $this->calendarEventsMemberAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);
    }

    public function hasModel(): bool
    {
        return null !== $this->model;
    }

    public function getModel(): ?CalendarEventsMemberModel
    {
        if (!$this->hasModel()) {
            throw new \Exception('Model not found. Please use the EventSubscriber::setModel() method first.');
        }

        return $this->model;
    }

    public function setModel(CalendarEventsMemberModel $model = null): void
    {
        if (null === $model) {
            $model = new CalendarEventsMemberModel();
        }

        $this->model = $model;
    }

    public function getModelFromBookingToken(string $strToken = ''): ?CalendarEventsMemberModel
    {
        return $this->calendarEventsMemberAdapter->findOneByBookingToken($strToken);
    }

    public function validateSubscription(EventConfig $eventConfig, $module = null): bool
    {
        // Trigger validate event booking request: Check if event is fully booked, if registration deadline has reached, duplicate entries, etc.
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_REGISTRATION]) || \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_REGISTRATION])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_REGISTRATION] as $callback) {
                $isValid = $this->systemAdapter->importStatic($callback[0])->{$callback[1]}($this, $eventConfig);

                if (!$isValid) {
                    return false;
                }
            }
        }

        return true;
    }

    public function subscribe(EventConfig $eventConfig, $module = null): void
    {
        $this->getModel()->pid = $eventConfig->getModel()->id;
        $this->getModel()->tstamp = time();
        $this->getModel()->dateAdded = time();
        $this->getModel()->bookingState = $module::CASE_WAITING_LIST_POSSIBLE === $module->case ? BookingState::STATE_WAITING_LIST : $eventConfig->get('bookingState');
        $this->getModel()->bookingToken = Uuid::uuid4()->toString();

        // Set the booking type
        $user = $this->security->getUser();
        $this->getModel()->bookingType = $user instanceof FrontendUser ? BookingType::TYPE_MEMBER : BookingType::TYPE_GUEST;

        // Trigger format form data hook: format/manipulate user input. E.g. convert formatted dates to timestamps, etc.';
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PREPARE_FORM_DATA]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PREPARE_FORM_DATA])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PREPARE_FORM_DATA] as $callback) {
                $this->systemAdapter->importStatic($callback[0])->{$callback[1]}($this->getForm(), $eventConfig, $this->getModel());
            }
        }

        // Trigger pre-booking hook: add your custom code here.
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_BOOKING]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_BOOKING])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_BOOKING] as $callback) {
                $this->systemAdapter->importStatic($callback[0])->{$callback[1]}($this->getForm(), $eventConfig, $this->getModel());
            }
        }

        // Save to Database
        $this->getModel()->save();

        // Trigger post-booking hook: add data to the session, send notifications, log things, etc.
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_POST_BOOKING]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_POST_BOOKING])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_POST_BOOKING] as $callback) {
                $this->systemAdapter->importStatic($callback[0])->{$callback[1]}($eventConfig, $this);
            }
        }
    }

    public function unsubscribe(): void
    {
        $eventMember = $this->getModel();
        $eventMember->bookingState = BookingState::STATE_UNSUBSCRIBED;
        $eventMember->save();
    }

    public function hasForm(): bool
    {
        return null !== $this->form;
    }

    public function getForm(): Form
    {
        if (!$this->hasForm()) {
            throw new \Exception('Form not found. Please use the EventSubscriber::createForm() method first.');
        }

        return $this->form;
    }

    /**
     * @param $module
     *
     * @throws \Exception
     */
    public function createForm(int $formId, EventConfig $eventConfig, $module = null): void
    {
        if (null === FormModel::findByPk($formId)) {
            throw new \Exception('Invalid or missing Contao form id.');
        }

        $eventMember = $this->getModel();

        $request = $this->requestStack->getCurrentRequest();

        $form = new Form(
            'eventSubscriptionForm',
            'POST',
            static fn ($objHaste) => $request->request->get('FORM_SUBMIT') === $objHaste->getFormId()
        );

        // Bind the event member model to the form input
        $form->bindModel($eventMember);

        // Add fields from form generator
        $form->addFieldsFromFormGenerator(
            $formId,
            function (&$strField, &$arrDca) use ($form, $eventConfig, $module) {
                // Trigger add field hook
                if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_ADD_FIELD]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_ADD_FIELD])) {
                    foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_ADD_FIELD] as $callback) {
                        $blnShow = $this->systemAdapter->importStatic($callback[0])->{$callback[1]}($form, $strField, $arrDca, $eventConfig, $module);

                        if (!$blnShow) {
                            return false;
                        }
                    }
                }

                // Return "true", otherwise the field will be skipped
                return true;
            }
        );

        $this->form = $form;
    }

    public function getModuleData(): array
    {
        return $this->moduleData;
    }

    public function setModuleData(array $arrData): void
    {
        $this->moduleData = $arrData;
    }

    public function getTable(): string
    {
        return self::TABLE;
    }
}
