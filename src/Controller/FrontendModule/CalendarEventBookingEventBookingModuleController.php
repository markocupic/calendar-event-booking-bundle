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

use Contao\CalendarEventsModel;
use Contao\Config;
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
use Haste\Form\Form;
use Haste\Util\Url;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
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
    public const CASE_BOOKING_NO_LONGER_POSSIBLE = 'bookingNoLongerPossible';
    public const CASE_BOOKING_NOT_YET_POSSIBLE = 'bookingNotYetPossible';

    private ContaoFramework $framework;
    private EventRegistration $eventRegistration;
    private TranslatorInterface $translator;
    private ScopeMatcher $scopeMatcher;
    private RequestStack $requestStack;

    // Adapters
    private Adapter $system;
    private Adapter $environment;
    private Adapter $stringUtil;
    private Adapter $url;
    private Adapter $message;
    private Adapter $date;
    private Adapter $config;

    private ?CalendarEventsModel $objEvent = null;
    private ?CalendarEventsMemberModel $objEventMember = null;
    private ?Form $objForm = null;
    private ?PageModel $objPage = null;
    private ?ModuleModel $model = null;

    public function __construct(ContaoFramework $framework, EventRegistration $eventRegistration, TranslatorInterface $translator, ScopeMatcher $scopeMatcher, RequestStack $requestStack)
    {
        $this->framework = $framework;
        $this->eventRegistration = $eventRegistration;
        $this->translator = $translator;
        $this->scopeMatcher = $scopeMatcher;
        $this->requestStack = $requestStack;

        // Adapters
        $this->system = $this->framework->getAdapter(System::class);
        $this->environment = $this->framework->getAdapter(Environment::class);
        $this->stringUtil = $this->framework->getAdapter(StringUtil::class);
        $this->url = $this->framework->getAdapter(Url::class);
        $this->message = $this->framework->getAdapter(Message::class);
        $this->date = $this->framework->getAdapter(Date::class);
        $this->config = $this->framework->getAdapter(Config::class);
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        $this->model = $model;

        // Is frontend
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->objPage = $page;
            $this->objEvent = $this->eventRegistration->getEventFromCurrentUrl();

            $showEmpty = true;

            // Get the current event && return an empty string
            // if addBookingForm isn't set or event is not published
            if (null !== $this->objEvent) {
                if ($this->objEvent->addBookingForm && $this->objEvent->published) {
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
    public function setProperty(string $key, $varValue): bool
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

        if (null === $this->objEvent) {
            throw new PageNotFoundException('Page not found: '.$this->environment->get('uri'));
        }

        // Override the page title (see #2853 and #4955)
        if ('' !== $this->objEvent->title) {
            $this->objPage->pageTitle = strip_tags($this->stringUtil->stripInsertTags($this->objEvent->title));
        }

        // Get case
        $case = $this->eventRegistration->getRegistrationState($this->objEvent);

        // Trigger set case hook: manipulate case
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE] as $callback) {
                $this->system->importStatic($callback[0])->{$callback[1]}($this);
            }
        }

        if (self::CASE_BOOKING_NOT_YET_POSSIBLE === $case) {
            $this->message->addInfo(
                $this->translator->trans(
                    'MSC.'.self::CASE_BOOKING_NOT_YET_POSSIBLE,
                    [$this->date->parse($this->config->get('dateFormat'), $this->objEvent->bookingStartDate)],
                    'contao_default'
                )
            );
        }

        if (self::CASE_BOOKING_NO_LONGER_POSSIBLE === $case) {
            $this->message->addInfo(
                $this->translator->trans(
                    'MSC.'.self::CASE_BOOKING_NO_LONGER_POSSIBLE,
                    [],
                    'contao_default'
                )
            );
        }

        if (self::CASE_EVENT_FULLY_BOOKED === $case) {
            $this->message->addInfo(
                $this->translator->trans(
                    'MSC.'.self::CASE_EVENT_FULLY_BOOKED,
                    [],
                    'contao_default'
                )
            );
        }

        if (self::CASE_BOOKING_POSSIBLE === $case) {
            if ($this->model->form && null !== ($objFormGeneratorModel = FormModel::findByPk($this->model->form))) {
                $this->setForm($objFormGeneratorModel);

                // Trigger pre validate hook: e.g. add custom field validators.';
                if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM])) {
                    foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM] as $callback) {
                        $this->system->importStatic($callback[0])->{$callback[1]}($this);
                    }
                }

                if ($this->objForm->validate()) {
                    if ($this->validateEventRegistration()) {
                        $this->objEventMember->pid = $this->objEvent->id;
                        $this->objEventMember->tstamp = time();
                        $this->objEventMember->addedOn = time();
                        $this->bookingState = $this->objEvent->bookingState;
                        $this->objEventMember->bookingToken = Uuid::uuid4()->toString();

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
                                $this->system->importStatic($callback[0])->{$callback[1]}($this);
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
                    }
                }
                $template->form = $this->objForm;
            }
        }

        $template->case = $case;
        $template->countBookings = $template->bookingCount = $this->eventRegistration->getBookingCount($this->objEvent);
        $template->bookingMin = $this->eventRegistration->getBookingMin($this->objEvent);
        $template->bookingMax = $this->eventRegistration->getBookingMax($this->objEvent);
        $template->event = $this->objEvent;
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
                        $blnShow = $this->system->importStatic($callback[0])->{$callback[1]}($this->objForm, $strField, $arrDca, $this->objEvent, $this);

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

    protected function validateEventRegistration(): bool
    {
        // Trigger validate event booking request: Check if event is fully booked, if registration deadline has reached, duplicate entries, etc.
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_BOOKING_REQUEST]) || \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_BOOKING_REQUEST])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_BOOKING_REQUEST] as $callback) {
                $isValid = $this->system->importStatic($callback[0])->{$callback[1]}($this);

                if (!$isValid) {
                    return false;
                }
            }
        }

        return true;
    }
}
