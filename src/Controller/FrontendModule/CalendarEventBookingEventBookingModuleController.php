<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Environment;
use Contao\FormModel;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Haste\Form\Form;
use Haste\Util\Url;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Helper\Formatter;
use Markocupic\CalendarEventBookingBundle\Logger\Logger;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationHelper;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @FrontendModule(type=CalendarEventBookingEventBookingModuleController::TYPE, category="events", )
 */
class CalendarEventBookingEventBookingModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_event_booking_module';

    public const EVENT_SUBSCRIPTION_TABLE = 'tl_calendar_events_member';

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var EventRegistration
     */
    private $eventRegistration;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var Logger
     */
    private $logger;

    private $scopeMatcher;

    /**
     * @var CalendarEventsModel
     */
    private $objEvent;

    /**
     * @var PageModel
     */
    private $objPage;

    public function __construct(ContaoFramework $framework, EventRegistration $eventRegistration, Formatter $formatter, TranslatorInterface $translator, NotificationHelper $notificationHelper, Logger $logger, ScopeMatcher $scopeMatcher)
    {
        $this->framework = $framework;
        $this->eventRegistration = $eventRegistration;
        $this->formatter = $formatter;
        $this->translator = $translator;
        $this->notificationHelper = $notificationHelper;
        $this->logger = $logger;
        $this->scopeMatcher = $scopeMatcher;
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Is frontend
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->objPage = $page;
            $this->objEvent = $this->eventRegistration->getEventFromCurrentUrl();

            $showEmpty = true;

            // Get the current event && return empty string if addBookingForm isn't set or event is not published
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
        return parent::__invoke($request, $model, $section, $classes);
    }

    public function validate(Form $objForm): bool
    {
        $systemAdapter = $this->framework->getAdapter(System::class);

        // HOOK: Validators
        if (isset($GLOBALS['TL_HOOKS']['calEvtBookingValidateSubscriptionRequest']) && \is_array($GLOBALS['TL_HOOKS']['calEvtBookingValidateSubscriptionRequest'])) {
            foreach ($GLOBALS['TL_HOOKS']['calEvtBookingValidateSubscriptionRequest'] as $callback) {
                if (!$systemAdapter->importStatic($callback[0])->{$callback[1]}($objForm, $this->objEvent)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return Response|null
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $systemAdapter = $this->framework->getAdapter(System::class);
        $environmentAdapter = $this->framework->getAdapter(Environment::class);
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);

        // Load language file
        $systemAdapter->loadLanguageFile(self::EVENT_SUBSCRIPTION_TABLE);

        if (null === $this->objEvent) {
            throw new PageNotFoundException('Page not found: '.$environmentAdapter->get('uri'));
        }

        // Overwrite the page title (see #2853 and #4955)
        if ('' !== $this->objEvent->title) {
            $this->objPage->pageTitle = strip_tags($stringUtilAdapter->stripInsertTags($this->objEvent->title));
        }

        $case = $this->eventRegistration->getRegistrationState($this->objEvent);

        switch ($case) {
            case 'bookingPossible':
                if ($model->form > 0 && null !== ($objForm = FormModel::findByPk($model->form))) {
                    $template->form = $this->getForm($objForm);
                }
                break;

            case 'bookingNotYetPossible':
                break;

            case 'bookingNoLongerPossible':
                break;

            case 'eventFullyBooked':
                break;
        }

        $template->case = $case;
        $template->countBookings = $template->bookingCount = $this->eventRegistration->getBookingCount($this->objEvent);
        $template->bookingMin = $this->eventRegistration->getBookingMin($this->objEvent);
        $template->event = $this->objEvent;
        $template->user = $this->eventRegistration->getLoggedInFrontendUser();
        $template->model = $model;

        return $template->getResponse();
    }

    private function getForm(FormModel $objFormModel): Form
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $urlAdapter = $this->framework->getAdapter(Url::class);
        $systemAdapter = $this->framework->getAdapter(System::class);
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        $objForm = new Form(
            'eventSubscriptionForm',
            'POST',
            static function ($objHaste) use ($inputAdapter) {
                return $inputAdapter->post('FORM_SUBMIT') === $objHaste->getFormId();
            }
        );

        // Bind the event member model
        $objEventMemberModel = new CalendarEventsMemberModel();
        $objForm->bindModel($objEventMemberModel);

        // Add fields from form generator
        $objForm->addFieldsFromFormGenerator(
            $objFormModel->id,
            function (&$strField, &$arrDca) use ($systemAdapter, $objForm) {
                $blnShow = true;

                // Trigger calEvtBookingPostBooking hook
                if (!empty($GLOBALS['TL_HOOKS']['calEvtBookingAddField']) || \is_array($GLOBALS['TL_HOOKS']['calEvtBookingAddField'])) {
                    foreach ($GLOBALS['TL_HOOKS']['calEvtBookingAddField'] as $callback) {
                        $blnShow = $systemAdapter
                            ->importStatic($callback[0])
                            ->{$callback[1]}($objForm, $strField, $arrDca, $this->objEvent, $this)
                        ;

                        if (!$blnShow) {
                            return false;
                        }
                    }
                }

                // Return true otherwise the field will be skipped
                return true;
            }
        );

        if ($objForm->validate()) {
            if ($this->validate($objForm)) {
                foreach (array_keys($objForm->getFormFields()) as $strFieldname) {
                    $strTable = self::EVENT_SUBSCRIPTION_TABLE;
                    $varValue = $objEventMemberModel->$strFieldname;
                    $varValue = $this->formatter->convertDateFormatsToTimestamps($varValue, $strTable, $strFieldname);
                    $varValue = $this->formatter->formatEmail($varValue, $strTable, $strFieldname);
                    $varValue = $this->formatter->getCorrectEmptyValue($varValue, $strTable, $strFieldname);
                    $objEventMemberModel->$strFieldname = $varValue;
                }

                $objEventMemberModel->pid = $this->objEvent->id;
                $objEventMemberModel->tstamp = time();
                $objEventMemberModel->addedOn = time();
                $objEventMemberModel->bookingToken = Uuid::uuid4()->toString();
                $objEventMemberModel->save();

                // Trigger calEvtBookingPostBooking hook
                if (!empty($GLOBALS['TL_HOOKS']['calEvtBookingPostBooking']) || \is_array($GLOBALS['TL_HOOKS']['calEvtBookingPostBooking'])) {
                    foreach ($GLOBALS['TL_HOOKS']['calEvtBookingPostBooking'] as $callback) {
                        $systemAdapter
                            ->importStatic($callback[0])
                            ->{$callback[1]}($objForm, $objEventMemberModel, $this->objEvent, $objCalendarEventsMemberModel, $this)
                        ;
                    }
                }

                // Log
                $this->logger->log($this->objEvent);

                // Send notification
                $this->notificationHelper->notify($objEventMemberModel, $this->objEvent);

                // Redirect to the jumpTo page
                if ($objFormModel->jumpTo) {
                    $objPageModel = $pageModelAdapter->findByPk($objFormModel->jumpTo);

                    if (null !== $objPageModel) {
                        $strRedirectUrl = $urlAdapter->addQueryString('bookingToken='.$objEventMemberModel->bookingToken, $objPageModel->getFrontendUrl());
                        $controllerAdapter->redirect($strRedirectUrl);
                    }
                }
            }
        }

        return $objForm;
    }
}
