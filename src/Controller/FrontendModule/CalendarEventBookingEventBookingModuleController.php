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
use Contao\FrontendUser;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Haste\Form\Form;
use Haste\Util\Url;
use Markocupic\CalendarEventBookingBundle\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\Booking\BookingType;
use Markocupic\CalendarEventBookingBundle\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\Helper\Event;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @FrontendModule(type=CalendarEventBookingEventBookingModuleController::TYPE, category="events")
 */
class CalendarEventBookingEventBookingModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_event_booking_module';
    public const EVENT_SUBSCRIPTION_TABLE = 'tl_calendar_events_member';
    public const CASE_BOOKING_FORM_DISABLED = 'bookingFormDisabled';
    public const CASE_BOOKING_POSSIBLE = 'bookingPossible';
    public const CASE_EVENT_FULLY_BOOKED = 'eventFullyBooked';
    public const CASE_WAITING_LIST_POSSIBLE = 'waitingListPossible';
    public const CASE_BOOKING_NO_LONGER_POSSIBLE = 'bookingNoLongerPossible';
    public const CASE_BOOKING_NOT_YET_POSSIBLE = 'bookingNotYetPossible';

    public ?EventConfig $eventConfig = null;
    public ?CalendarEventsMemberModel $objEventMember = null;
    public ?Form $objForm = null;
    public ?PageModel $objPage = null;
    public ?ModuleModel $model = null;
    public ?string $case = null;

    private ContaoFramework $framework;
    private Security $security;
    private EventRegistration $eventRegistration;
    private TranslatorInterface $translator;
    private ScopeMatcher $scopeMatcher;
    private RequestStack $requestStack;
    private Event $eventHelper;
    private EventFactory $eventFactory;

    // Adapters
    private Adapter $system;
    private Adapter $environment;
    private Adapter $stringUtil;
    private Adapter $url;
    private Adapter $message;
    private Adapter $date;
    private Adapter $config;
    private Adapter $controller;

    public function __construct(ContaoFramework $framework, Security $security, EventRegistration $eventRegistration, TranslatorInterface $translator, ScopeMatcher $scopeMatcher, RequestStack $requestStack, Event $eventHelper, EventFactory $eventFactory)
    {
        $this->framework = $framework;
        $this->security = $security;
        $this->eventRegistration = $eventRegistration;
        $this->translator = $translator;
        $this->scopeMatcher = $scopeMatcher;
        $this->requestStack = $requestStack;
        $this->eventHelper = $eventHelper;
        $this->eventFactory = $eventFactory;

        // Adapters
        $this->system = $this->framework->getAdapter(System::class);
        $this->environment = $this->framework->getAdapter(Environment::class);
        $this->stringUtil = $this->framework->getAdapter(StringUtil::class);
        $this->url = $this->framework->getAdapter(Url::class);
        $this->message = $this->framework->getAdapter(Message::class);
        $this->date = $this->framework->getAdapter(Date::class);
        $this->config = $this->framework->getAdapter(Config::class);
        $this->controller = $this->framework->getAdapter(Controller::class);
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        $this->model = $model;

        // Is frontend
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->objPage = $page;

            $showEmpty = true;

            // Get the current event && return an empty string
            // if activateBookingForm isn't set or event is not published
            if (null !== ($event = $this->eventHelper->getEventFromCurrentUrl())) {
                $this->eventConfig = $this->eventFactory->create((int) $event->id);

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
     *
     * @return mixed
     */
    public function getProperty(string $key)
    {
        if (!property_exists($this, $key)) {
            throw new \Exception(sprintf('Property "%s" not found.', $key));
        }

        return $this->$key;
    }

    /**
     * @param mixed $varValue
     *
     * @throws \Exception
     */
    public function set(string $key, $varValue): bool
    {
        if (property_exists($this, $key)) {
            $this->$key = $varValue;

            return true;
        }

        throw new \Exception(sprintf('Property "%s" not found.', $key));
    }

    /**
     * @throws \Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // Load language file
        $this->system->loadLanguageFile(self::EVENT_SUBSCRIPTION_TABLE);

        if (null === $this->eventConfig) {
            throw new PageNotFoundException('Page not found: '.$this->environment->get('uri'));
        }

        // Override the page title (see #2853 and #4955)
        if ('' !== $this->eventConfig->get('title')) {
            $this->objPage->pageTitle = strip_tags($this->stringUtil->stripInsertTags($this->eventConfig->get('title')));
        }

        // Get case
        $this->case = $this->eventRegistration->getRegistrationCase($this->eventConfig);

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

        // If display booking form
        if (self::CASE_BOOKING_POSSIBLE === $this->case || self::CASE_WAITING_LIST_POSSIBLE === $this->case) {
            if ($this->model->form && null !== ($objFormGeneratorModel = FormModel::findByPk($this->model->form))) {
                $this->setForm($objFormGeneratorModel);

                // Trigger pre validate hook: e.g. add custom field validators.';
                if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM])) {
                    foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM] as $callback) {
                        $this->system->importStatic($callback[0])->{$callback[1]}($this);
                    }
                }

                if ($this->objForm->validate()) {
                    if ($this->validateRegistration()) {
                        $this->objEventMember->pid = $this->eventConfig->get('id');
                        $this->objEventMember->tstamp = time();
                        $this->objEventMember->dateAdded = time();
                        $this->objEventMember->bookingState = self::CASE_WAITING_LIST_POSSIBLE === $this->case ? BookingState::STATE_WAITING_LIST : $this->eventConfig->get('bookingState');
                        $this->objEventMember->bookingToken = Uuid::uuid4()->toString();

                        // Set the booking type
                        $user = $this->security->getUser();
                        $this->objEventMember->bookingType = $user instanceof FrontendUser ? BookingType::TYPE_MEMBER : BookingType::TYPE_GUEST;

                        // Trigger format form data hook: format/manipulate user input. E.g. convert formatted dates to timestamps, etc.';
                        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PREPARE_FORM_DATA]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PREPARE_FORM_DATA])) {
                            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PREPARE_FORM_DATA] as $callback) {
                                $this->system->importStatic($callback[0])->{$callback[1]}($this);
                            }
                        }

                        // Trigger pre-booking hook: add your custom code here.
                        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_BOOKING]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_BOOKING])) {
                            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_BOOKING] as $callback) {
                                $this->system->importStatic($callback[0])->{$callback[1]}($this);
                            }
                        }

                        // Save to Database
                        $this->objEventMember->save();

                        // Trigger post-booking hook: add data to the session, send notifications, log things, etc.
                        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_POST_BOOKING]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_POST_BOOKING])) {
                            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_POST_BOOKING] as $callback) {
                                $this->system->importStatic($callback[0])->{$callback[1]}($this, (int) $this->objEventMember->id);
                            }
                        }

                        // Redirect to the jumpTo page
                        if (null !== $objFormGeneratorModel && $objFormGeneratorModel->jumpTo) {
                            /** @var PageModel $jumpToPage */
                            $jumpToPage = $this->objPage->findByPk($objFormGeneratorModel->jumpTo);

                            if (null !== $jumpToPage) {
                                $strRedirectUrl = $this->url->addQueryString(
                                    'bookingToken='.$this->objEventMember->bookingToken,
                                    $jumpToPage->getAbsoluteUrl()
                                )
                                ;

                                return new RedirectResponse($strRedirectUrl);
                            }
                        }

                        $this->controller->reload();
                    }
                }

                $template->form = $this->objForm;
            }
        }

        $template->case = $this->case;
        $template->countBookings = $template->bookingCount = $this->eventRegistration->getBookingCount($this->eventConfig);
        $template->bookingMin = $this->eventConfig->getBookingMin();
        $template->bookingMax = $this->eventConfig->getBookingMax();
        $template->event = $this->eventConfig->event;
        $template->eventConfig = $this->eventConfig;
        $template->model = $this->model;
        $template->messages = $this->message->hasMessages() ? $this->message->generate('FE') : null;

        return $template->getResponse();
    }

    protected function setForm(FormModel $objFormGeneratorModel): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $this->objForm = new Form(
            'eventSubscriptionForm',
            'POST',
            static fn ($objHaste) => $request->request->get('FORM_SUBMIT') === $objHaste->getFormId()
        );

        // Bind the event member model to the form input
        $this->objEventMember = new CalendarEventsMemberModel();
        $this->objForm->bindModel($this->objEventMember);

        // Add fields from form generator
        $this->objForm->addFieldsFromFormGenerator(
            $objFormGeneratorModel->id,
            function (&$strField, &$arrDca) {
                // Trigger add field hook
                if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_ADD_FIELD]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_ADD_FIELD])) {
                    foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_ADD_FIELD] as $callback) {
                        $blnShow = $this->system->importStatic($callback[0])->{$callback[1]}($this->objForm, $strField, $arrDca, $this->eventConfig, $this);

                        if (!$blnShow) {
                            return false;
                        }
                    }
                }

                // Return "true", otherwise the field will be skipped
                return true;
            }
        );
    }

    protected function validateRegistration(): bool
    {
        // Trigger validate event booking request: Check if event is fully booked, if registration deadline has reached, duplicate entries, etc.
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_REGISTRATION]) || \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_REGISTRATION])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_REGISTRATION] as $callback) {
                $isValid = $this->system->importStatic($callback[0])->{$callback[1]}($this);

                if (!$isValid) {
                    return false;
                }
            }
        }

        return true;
    }
}
