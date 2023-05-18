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

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Date;
use Contao\Environment;
use Contao\FormModel;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\Template;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventBooking\Template\AddTemplateData;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(CalendarEventBookingEventBookingModuleController::TYPE, category:'events', template: 'mod_calendar_event_booking_event_booking_module')]
class CalendarEventBookingEventBookingModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_event_booking_module';
    public const FORM_SUBMIT_ID = 'event_subscription_form_%s';
    public const CASE_EVENT_NOT_BOOKABLE = 'eventNotBookable';
    public const CASE_BOOKING_POSSIBLE = 'bookingPossible';
    public const CASE_EVENT_FULLY_BOOKED = 'eventFullyBooked';
    public const CASE_WAITING_LIST_POSSIBLE = 'waitingListPossible';
    public const CASE_BOOKING_NO_LONGER_POSSIBLE = 'bookingNoLongerPossible';
    public const CASE_BOOKING_NOT_YET_POSSIBLE = 'bookingNotYetPossible';

    public EventConfig|null $eventConfig = null;
    public PageModel|null $objPage = null;
    public ModuleModel|null $model = null;
    public string|null $case = null;

    private Adapter $config;
    private Adapter $controller;
    private Adapter $date;
    private Adapter $environment;
    private Adapter $formModel;
    private Adapter $message;
    private Adapter $system;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly EventFactory $eventFactory,
        private readonly BookingValidator $bookingValidator,
        private readonly AddTemplateData $addTemplateData,
        private readonly EventRegistration $eventRegistration,
    ) {
        $this->config = $this->framework->getAdapter(Config::class);
        $this->controller = $this->framework->getAdapter(Controller::class);
        $this->date = $this->framework->getAdapter(Date::class);
        $this->environment = $this->framework->getAdapter(Environment::class);
        $this->formModel = $this->framework->getAdapter(FormModel::class);
        $this->message = $this->framework->getAdapter(Message::class);
        $this->system = $this->framework->getAdapter(System::class);
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        $this->model = $model;

        // Is frontend
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->objPage = $page;

            $showEmpty = true;

            // Get the current event
            // Return an empty string, if...
            // - activateBookingForm isn't set or
            // - event is not published
            if (null !== ($event = EventConfig::getEventFromCurrentUrl())) {
                $this->eventConfig = $this->eventFactory->create($event);

                if ($this->eventConfig->get('activateBookingForm') && $this->eventConfig->get('published')) {
                    $showEmpty = false;
                }
            }

            if ($showEmpty) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

        // Call the parent method
        return parent::__invoke($request, $this->model, $section, $classes);
    }

    public function getEventRegistrationHelper(): EventRegistration|null
    {
        return $this->eventRegistration;
    }

    public function getEvent(): CalendarEventsModel|null
    {
        return $this->eventConfig?->getModel();
    }

    public function getCase(): string|null
    {
        return $this->case;
    }

    /**
     * @throws \Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $contaoFormId = (int) $this->model->form;
        $formSubmitId = sprintf(static::FORM_SUBMIT_ID, $contaoFormId);

        // Load language file
        $this->system->loadLanguageFile($this->eventRegistration->getTable());

        if (null === $this->eventConfig) {
            throw new PageNotFoundException('Page not found: '.$this->environment->get('uri'));
        }

        // Get case
        $this->case = $this->getRegistrationCase($this->eventConfig);
        $template->caseText = null;

        // Trigger set case hook: manipulate case
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE] as $callback) {
                $this->system->importStatic($callback[0])->{$callback[1]}($this);
            }
        }

        // Display messages
        if (self::CASE_BOOKING_NOT_YET_POSSIBLE === $this->case) {
            if ($formSubmitId !== $request->request->get('FORM_SUBMIT')) {
                $template->caseText = $this->translator->trans(
                    'MSC.'.$this->case,
                    [$this->date->parse($this->config->get('dateFormat'), $this->eventConfig->get('bookingStartDate'))],
                    'contao_default'
                );
            }
        } elseif (self::CASE_BOOKING_NO_LONGER_POSSIBLE === $this->case) {
            if ($formSubmitId !== $request->request->get('FORM_SUBMIT')) {
                $template->caseText = $this->translator->trans(
                    'MSC.'.$this->case,
                    [],
                    'contao_default'
                );
            }
        } elseif (self::CASE_EVENT_FULLY_BOOKED === $this->case) {
            if ($formSubmitId !== $request->request->get('FORM_SUBMIT')) {
                $template->caseText = $this->translator->trans(
                    'MSC.'.$this->case,
                    [],
                    'contao_default'
                );
            }
        } elseif (self::CASE_WAITING_LIST_POSSIBLE === $this->case) {
            if ($formSubmitId !== $request->request->get('FORM_SUBMIT')) {
                $template->caseText = $this->translator->trans(
                    'MSC.'.$this->case,
                    [],
                    'contao_default'
                );
            }
        } elseif (self::CASE_BOOKING_POSSIBLE === $this->case) {
            if ($formSubmitId !== $request->request->get('FORM_SUBMIT')) {
                $template->caseText = $this->translator->trans(
                    'MSC.'.$this->case,
                    [],
                    'contao_default'
                );
            }
        }

        $template->form = null;

        // If display booking form (regular subscription or subscription to the waiting list is possible)
        if ($this->bookingValidator->validateCanRegister($this->eventConfig)) {
            $this->eventRegistration->setModuleData($model->row());

            // Create a new CalendarEventsMember model
            $this->eventRegistration->setModel();

            // Create the form
            $this->eventRegistration->createForm($contaoFormId, $formSubmitId, $this->eventConfig, $this);

            // Trigger pre validate hook: e.g. add custom field validators.';
            if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM])) {
                foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM] as $callback) {
                    $this->system->importStatic($callback[0])->{$callback[1]}($this);
                }
            }

            if ($this->eventRegistration->getForm()->validate()) {
                // Use an event registration helper class to
                // validate subscriptions and
                // write the new event subscription to the database
                if ($this->eventRegistration->validateSubscription($this->eventConfig)) {
                    $this->eventRegistration->subscribe($this->eventConfig);

                    // Reload or redirect to the jumpTo page
                    if (null !== ($formModel = $this->formModel->findByPk($this->model->form))) {
                        /** @var PageModel $jumpTo */
                        $jumpTo = $this->objPage->findByPk($formModel->jumpTo);

                        if (null !== $jumpTo) {
                            $request->query->add(['bookingToken' => $this->eventRegistration->getModel()->bookingToken]);
                            $request->overrideGlobals();
                            $this->controller->redirect($request->getUri());
                        }
                    }

                    $this->controller->reload();
                }
            }

            $template->form = $this->eventRegistration->getForm();
        }

        $template->case = $this->case;
        $template->model = $this->model;
        $template->messages = $this->message->hasMessages() ? $this->message->generate('FE') : null;

        // Augment template with more data
        $this->addTemplateData->addTemplateData($this->eventConfig, $template);

        return $template->getResponse();
    }

    /**
     * @throws Exception
     */
    private function getRegistrationCase(EventConfig $eventConfig): string
    {
        if (!$eventConfig->isBookable()) {
            $state = self::CASE_EVENT_NOT_BOOKABLE;
        } elseif (!$this->bookingValidator->validateBookingStartDate($eventConfig)) {
            $state = self::CASE_BOOKING_NOT_YET_POSSIBLE;
        } elseif (!$this->bookingValidator->validateBookingEndDate($eventConfig)) {
            $state = self::CASE_BOOKING_NO_LONGER_POSSIBLE;
        } elseif ($this->bookingValidator->validateBookingMax($eventConfig, 1)) {
            $state = self::CASE_BOOKING_POSSIBLE;
        } elseif ($this->bookingValidator->validateBookingMaxWaitingList($eventConfig, 1)) {
            $state = self::CASE_WAITING_LIST_POSSIBLE;
        } else {
            $state = self::CASE_EVENT_FULLY_BOOKED;
        }

        return $state;
    }
}
