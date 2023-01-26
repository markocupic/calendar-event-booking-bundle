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

use Codefog\HasteBundle\Form\Form;
use Codefog\HasteBundle\UrlParser;
use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Date;
use Contao\Environment;
use Contao\FormModel;
use Contao\Input;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(CalendarEventBookingEventBookingModuleController::TYPE, category:'events', template: 'mod_calendar_event_booking_event_booking_module')]
class CalendarEventBookingEventBookingModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_event_booking_module';
    public const EVENT_SUBSCRIPTION_TABLE = 'tl_calendar_events_member';
    public const CASE_BOOKING_FORM_DISABLED = 'bookingFormDisabled';
    public const CASE_BOOKING_POSSIBLE = 'bookingPossible';
    public const CASE_EVENT_FULLY_BOOKED = 'eventFullyBooked';
    public const CASE_BOOKING_NO_LONGER_POSSIBLE = 'bookingNoLongerPossible';
    public const CASE_BOOKING_NOT_YET_POSSIBLE = 'bookingNotYetPossible';

    private ?CalendarEventsMemberModel $objEventMember = null;
    private ?CalendarEventsModel $objEvent = null;
    private ?Form $objForm = null;
    private ?ModuleModel $model = null;
    private ?PageModel$objPage = null;
    private ?string $case = null;
    private array $disabledHooks = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventRegistration $eventRegistration,
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
        private readonly UrlParser $urlParser,
    ) {
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
     */
    public function getProperty(string $key): mixed
    {
        if (!property_exists($this, $key)) {
            throw new \Exception(sprintf('Property "%s" not found.', $key));
        }

        return $this->$key;
    }

    /**
     * @throws \Exception
     */
    public function setProperty(string $key, mixed $varValue): bool
    {
        if (property_exists($this, $key)) {
            $this->$key = $varValue;

            return true;
        }

        throw new \Exception(sprintf('Property "%s" not found.', $key));
    }

    public function getEventRegistration(): ?CalendarEventsMemberModel
    {
        return $this->objEventMember;
    }

    public function getEvent(): ?CalendarEventsModel
    {
        return $this->objEvent;
    }

    public function getForm(): ?Form
    {
        return $this->objForm;
    }

    public function getCase(): ?string
    {
        return $this->case;
    }

    /**
     * @throws \Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $systemAdapter = $this->framework->getAdapter(System::class);
        $environmentAdapter = $this->framework->getAdapter(Environment::class);
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $messageAdapter = $this->framework->getAdapter(Message::class);
        $dateAdapter = $this->framework->getAdapter(Date::class);
        $configAdapter = $this->framework->getAdapter(Config::class);

        // Load language file
        $systemAdapter->loadLanguageFile(self::EVENT_SUBSCRIPTION_TABLE);

        if (null === $this->objEvent) {
            throw new PageNotFoundException('Page not found: '.$environmentAdapter->get('uri'));
        }

        // Override the page title (see #2853 and #4955)
        if ('' !== $this->objEvent->title) {
            $this->objPage->pageTitle = strip_tags($stringUtilAdapter->stripInsertTags($this->objEvent->title));
        }

        // Get case
        $this->case = $this->eventRegistration->getRegistrationState($this->objEvent);

        // Trigger set case hook: manipulate case
        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingSetCase']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingSetCase'])) {
            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingSetCase'] as $callback) {
                $systemAdapter->importStatic($callback[0])->{$callback[1]}($this, $this->disabledHooks);
            }
        }

        if (self::CASE_BOOKING_NOT_YET_POSSIBLE === $this->case) {
            $messageAdapter->addInfo(
                $this->translator->trans(
                    'MSC.'.self::CASE_BOOKING_NOT_YET_POSSIBLE,
                    [$dateAdapter->parse($configAdapter->get('dateFormat'), $this->objEvent->bookingStartDate)],
                    'contao_default'
                )
            );
        }

        if (self::CASE_BOOKING_NO_LONGER_POSSIBLE === $this->case) {
            $messageAdapter->addInfo(
                $this->translator->trans(
                    'MSC.'.self::CASE_BOOKING_NO_LONGER_POSSIBLE,
                    [],
                    'contao_default'
                )
            );
        }

        if (self::CASE_EVENT_FULLY_BOOKED === $this->case) {
            $messageAdapter->addInfo(
                $this->translator->trans(
                    'MSC.'.self::CASE_EVENT_FULLY_BOOKED,
                    [],
                    'contao_default'
                )
            );
        }

        if (self::CASE_BOOKING_POSSIBLE === $this->case) {
            if ($this->model->form && null !== ($objFormGeneratorModel = FormModel::findByPk($this->model->form))) {
                $this->setForm($objFormGeneratorModel);

                // Trigger pre validate hook: e.g. add custom field validators.';
                if (isset($GLOBALS['TL_HOOKS']['calEvtBookingPreValidate']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingPreValidate'])) {
                    foreach ($GLOBALS['TL_HOOKS']['calEvtBookingPreValidate'] as $callback) {
                        $systemAdapter->importStatic($callback[0])->{$callback[1]}($this, $this->disabledHooks);
                    }
                }

                if ($this->objForm->validate()) {
                    if ($this->validateEventRegistration()) {
                        $this->objEventMember->pid = $this->objEvent->id;
                        $this->objEventMember->tstamp = time();
                        $this->objEventMember->addedOn = time();
                        $this->objEventMember->bookingToken = Uuid::uuid4()->toString();

                        // Trigger format form data hook: format/manipulate user input. E.g. convert formatted dates to timestamps, etc.';
                        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingPrepareFormData']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingPrepareFormData'])) {
                            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingPrepareFormData'] as $callback) {
                                $systemAdapter->importStatic($callback[0])->{$callback[1]}($this, $this->disabledHooks);
                            }
                        }

                        // Trigger pre-booking hook: add your custom code here.
                        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingPreBooking']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingPreBooking'])) {
                            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingPreBooking'] as $callback) {
                                $systemAdapter->importStatic($callback[0])->{$callback[1]}($this, $this->disabledHooks);
                            }
                        }

                        // Save to Database
                        $this->objEventMember->save();

                        // Trigger post booking hook: add data to the session, send notifications, log things, etc.
                        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingPostBooking']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingPostBooking'])) {
                            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingPostBooking'] as $callback) {
                                $systemAdapter->importStatic($callback[0])->{$callback[1]}($this, $this->disabledHooks);
                            }
                        }

                        // Redirect to the jumpTo page
                        if (null !== $objFormGeneratorModel && $objFormGeneratorModel->jumpTo) {
                            /** @var PageModel $objPageModel */
                            $objPageModel = $pageModelAdapter->findByPk($objFormGeneratorModel->jumpTo);

                            if (null !== $objPageModel) {
                                $strRedirectUrl = $this->urlParser->addQueryString(
                                    'bookingToken='.$this->objEventMember->bookingToken,
                                    $objPageModel->getAbsoluteUrl()
                                );

                                return new RedirectResponse($strRedirectUrl);
                            }
                        }
                    }
                }
                $template->form = $this->objForm->generate();
            }
        }

        $template->case = $this->case;
        $template->countBookings = $template->bookingCount = $this->eventRegistration->getBookingCount($this->objEvent);
        $template->bookingMin = $this->eventRegistration->getBookingMin($this->objEvent);
        $template->bookingMax = $this->eventRegistration->getBookingMax($this->objEvent);
        $template->event = $this->objEvent;
        $template->model = $this->model;
        $template->messages = $messageAdapter->hasMessages() ? $messageAdapter->generate('FE') : null;

        return $template->getResponse();
    }

    protected function setForm(FormModel $objFormGeneratorModel): void
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $systemAdapter = $this->framework->getAdapter(System::class);

        $this->objForm = new Form(
            'eventSubscriptionForm',
            'POST',
            static fn ($objHaste) => $inputAdapter->post('FORM_SUBMIT') === $objHaste->getFormId()
        );

        // Bind the event member model to the form input
        $this->objEventMember = new CalendarEventsMemberModel();
        $this->objForm->setBoundModel($this->objEventMember);

        // Add fields from form generator
        $this->objForm->addFieldsFromFormGenerator(
            $objFormGeneratorModel->id,
            function (&$strField, &$arrDca) use ($systemAdapter) {
                // Trigger add field hook
                if (isset($GLOBALS['TL_HOOKS']['calEvtBookingAddField']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingAddField'])) {
                    foreach ($GLOBALS['TL_HOOKS']['calEvtBookingAddField'] as $callback) {
                        $blnShow = $systemAdapter->importStatic($callback[0])->{$callback[1]}($this->objForm, $strField, $arrDca, $this->objEvent, $this);

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
        $systemAdapter = $this->framework->getAdapter(System::class);

        // Trigger validate event booking request: Check if event is fully booked, if registration deadline has reached, duplicate entries, etc.
        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingValidateBookingRequest']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingValidateBookingRequest'])) {
            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingValidateBookingRequest'] as $callback) {
                $isValid = $systemAdapter->importStatic($callback[0])->{$callback[1]}($this, $this->disabledHooks);

                if (!$isValid) {
                    return false;
                }
            }
        }

        return true;
    }
}
