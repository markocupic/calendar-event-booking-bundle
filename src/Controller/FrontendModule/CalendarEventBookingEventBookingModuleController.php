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

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Date;
use Contao\Environment;
use Contao\FormModel;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Haste\Util\Url;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber\EventSubscriber;
use Markocupic\CalendarEventBookingBundle\EventBooking\Helper\AddTemplateData;
use Markocupic\CalendarEventBookingBundle\EventBooking\Helper\Event;
use Markocupic\CalendarEventBookingBundle\EventBooking\Validator\BookingValidator;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @FrontendModule(type=CalendarEventBookingEventBookingModuleController::TYPE, category="events")
 */
class CalendarEventBookingEventBookingModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_event_booking_module';

    public const CASE_EVENT_NOT_BOOKABLE = 'eventNotBookable';
    public const CASE_BOOKING_POSSIBLE = 'bookingPossible';
    public const CASE_EVENT_FULLY_BOOKED = 'eventFullyBooked';
    public const CASE_WAITING_LIST_POSSIBLE = 'waitingListPossible';
    public const CASE_BOOKING_NO_LONGER_POSSIBLE = 'bookingNoLongerPossible';
    public const CASE_BOOKING_NOT_YET_POSSIBLE = 'bookingNotYetPossible';

    public ?EventConfig $eventConfig = null;
    public ?PageModel $objPage = null;
    public ?ModuleModel $model = null;
    public ?string $case = null;

    private ContaoFramework $framework;
    private TranslatorInterface $translator;
    private ScopeMatcher $scopeMatcher;
    private Event $eventHelper;
    private EventFactory $eventFactory;
    private BookingValidator $bookingValidator;
    private AddTemplateData $addTemplateData;
    private EventSubscriber $eventSubscriber;

    // Adapters
    private Adapter $config;
    private Adapter $controller;
    private Adapter $date;
    private Adapter $environment;
    private Adapter $formModel;
    private Adapter $message;
    private Adapter $stringUtil;
    private Adapter $system;
    private Adapter $url;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator, ScopeMatcher $scopeMatcher, Event $eventHelper, EventFactory $eventFactory, BookingValidator $bookingValidator, AddTemplateData $addTemplateData, EventSubscriber $eventSubscriber)
    {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->scopeMatcher = $scopeMatcher;
        $this->eventHelper = $eventHelper;
        $this->eventFactory = $eventFactory;
        $this->bookingValidator = $bookingValidator;
        $this->addTemplateData = $addTemplateData;
        $this->eventSubscriber = $eventSubscriber;

        // Adapters
        $this->config = $this->framework->getAdapter(Config::class);
        $this->controller = $this->framework->getAdapter(Controller::class);
        $this->date = $this->framework->getAdapter(Date::class);
        $this->environment = $this->framework->getAdapter(Environment::class);
        $this->formModel = $this->framework->getAdapter(FormModel::class);
        $this->message = $this->framework->getAdapter(Message::class);
        $this->stringUtil = $this->framework->getAdapter(StringUtil::class);
        $this->system = $this->framework->getAdapter(System::class);
        $this->url = $this->framework->getAdapter(Url::class);
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
            if (null !== ($event = $this->eventHelper->getEventFromCurrentUrl())) {
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

    /**
     * @throws \Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // Load language file
        $this->system->loadLanguageFile($this->eventSubscriber->getTable());

        if (null === $this->eventConfig) {
            throw new PageNotFoundException('Page not found: '.$this->environment->get('uri'));
        }

        // Override the page title (see #2853 and #4955)
        if ('' !== $this->eventConfig->get('title')) {
            $this->objPage->pageTitle = strip_tags($this->stringUtil->stripInsertTags($this->eventConfig->get('title')));
        }

        // Get case
        $this->case = $this->getRegistrationCase($this->eventConfig);

        // Trigger set case hook: manipulate case
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE] as $callback) {
                $this->system->importStatic($callback[0])->{$callback[1]}($this);
            }
        }

        // Display messages
        if (self::CASE_BOOKING_NOT_YET_POSSIBLE === $this->case) {
            if ('eventSubscriptionForm' !== $request->request->get('FORM_SUBMIT')) {
                $this->message->addInfo(
                    $this->translator->trans(
                        'MSC.'.$this->case,
                        [$this->date->parse($this->config->get('dateFormat'), $this->eventConfig->get('bookingStartDate'))],
                        'contao_default'
                    )
                );
            }
        } elseif (self::CASE_BOOKING_NO_LONGER_POSSIBLE === $this->case) {
            if ('eventSubscriptionForm' !== $request->request->get('FORM_SUBMIT')) {
                $this->message->addInfo(
                    $this->translator->trans(
                        'MSC.'.$this->case,
                        [],
                        'contao_default'
                    )
                );
            }
        } elseif (self::CASE_EVENT_FULLY_BOOKED === $this->case) {
            if ('eventSubscriptionForm' !== $request->request->get('FORM_SUBMIT')) {
                $this->message->addInfo(
                    $this->translator->trans(
                        'MSC.'.$this->case,
                        [],
                        'contao_default'
                    )
                );
            }
        } elseif (self::CASE_WAITING_LIST_POSSIBLE === $this->case) {
            if ('eventSubscriptionForm' !== $request->request->get('FORM_SUBMIT')) {
                $this->message->addInfo(
                    $this->translator->trans(
                        'MSC.'.$this->case,
                        [],
                        'contao_default'
                    )
                );
            }
        }

        $template->form = null;

        // If display booking form
        if ($this->bookingValidator->validateCanRegister($this->eventConfig)) {
            $this->eventSubscriber->setModuleData($model->row());

            // Create a new CalendarEventsMember model
            $this->eventSubscriber->setModel();

            // Create the form
            $contaoFormId = (int) $this->model->form;
            $this->eventSubscriber->createForm($contaoFormId, $this->eventConfig, $this);

            // Trigger pre validate hook: e.g. add custom field validators.';
            if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM])) {
                foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM] as $callback) {
                    $this->system->importStatic($callback[0])->{$callback[1]}($this);
                }
            }

            if ($this->eventSubscriber->getForm()->validate()) {
                // Use an event subscriber class to
                // validate subscription and
                // write the new event member to the database
                if ($this->eventSubscriber->validateSubscription($this->eventConfig, $this)) {
                    $this->eventSubscriber->subscribe($this->eventConfig, $this);

                    // Reload or redirect to the jumpTo page
                    if (null !== ($formModel = $this->formModel->findByPk($this->model->form))) {
                        /** @var PageModel $jumpTo */
                        $jumpTo = $this->objPage->findByPk($formModel->jumpTo);

                        if (null !== $jumpTo) {
                            $url = $this->url->addQueryString(
                                'bookingToken='.$this->eventSubscriber->getModel()->bookingToken,
                                $jumpTo->getAbsoluteUrl()
                            );

                            $this->controller->redirect($url);
                        }
                    }

                    $this->controller->reload();
                }
            }

            $template->form = $this->eventSubscriber->getForm();
        }

        $template->case = $this->case;
        $template->model = $this->model;
        $template->messages = $this->message->hasMessages() ? $this->message->generate('FE') : null;

        // Augment template with more data
        $this->addTemplateData->addTemplateData($this->eventConfig, $template);

        return $template->getResponse();
    }

    private function getRegistrationCase(EventConfig $eventConfig): string
    {
        if (!$eventConfig->isBookable()) {
            $state = self::CASE_EVENT_NOT_BOOKABLE;
        } elseif (!$this->bookingValidator->validateBookingStartDate($eventConfig)) {
            $state = self::CASE_BOOKING_NOT_YET_POSSIBLE;
        } elseif (!$this->bookingValidator->validateBookingEndDate($eventConfig)) {
            $state = self::CASE_BOOKING_NO_LONGER_POSSIBLE;
        } elseif (!$this->bookingValidator->validateBookingMax($eventConfig, 1, false) && $this->bookingValidator->validateBookingMax($eventConfig, 1, true)) {
            $state = self::CASE_WAITING_LIST_POSSIBLE;
        } elseif (!$this->bookingValidator->validateBookingMax($eventConfig, 1, true)) {
            $state = self::CASE_EVENT_FULLY_BOOKED;
        } elseif ($this->bookingValidator->validateBookingMax($eventConfig, 1, false)) {
            $state = self::CASE_BOOKING_POSSIBLE;
        } else {
            throw new \LogicException('Invalid registration case detected.');
        }

        return $state;
    }
}
