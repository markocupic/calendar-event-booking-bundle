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
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
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
use Haste\Form\Form;
use Haste\Util\Url;
use Markocupic\CalendarEventBookingBundle\Event\FormatFormDataEvent;
use Markocupic\CalendarEventBookingBundle\Event\PostBookingEvent;
use Markocupic\CalendarEventBookingBundle\Event\ValidateEventRegistrationRequestEvent;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispather;

    /**
     * @var CalendarEventsModel
     */
    private $objEvent;

    /**
     * Possible cases are:
     * bookingNotYetPossible, bookingNoLongerPossible, eventFullyBooked, bookingPossible.
     *
     * @var string
     */
    private $case;

    /**
     * @var CalendarEventsMemberModel
     */
    private $objEventMember;

    /**
     * @var Form
     */
    private $objForm;

    /**
     * @var PageModel
     */
    private $objPage;

    public function __construct(ContaoFramework $framework, EventRegistration $eventRegistration, TranslatorInterface $translator, ScopeMatcher $scopeMatcher, EventDispatcherInterface $eventDispatcher)
    {
        $this->framework = $framework;
        $this->eventRegistration = $eventRegistration;
        $this->translator = $translator;
        $this->scopeMatcher = $scopeMatcher;
        $this->eventDispatcher = $eventDispatcher;
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

    /**
     * @throws \Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $systemAdapter = $this->framework->getAdapter(System::class);
        $environmentAdapter = $this->framework->getAdapter(Environment::class);
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $urlAdapter = $this->framework->getAdapter(Url::class);
        $messageAdapter = $this->framework->getAdapter(Message::class);
        $dateAdapter = $this->framework->getAdapter(Date::class);

        // Load language file
        $systemAdapter->loadLanguageFile(self::EVENT_SUBSCRIPTION_TABLE);

        if (null === $this->objEvent) {
            throw new PageNotFoundException('Page not found: '.$environmentAdapter->get('uri'));
        }

        // Overwrite the page title (see #2853 and #4955)
        if ('' !== $this->objEvent->title) {
            $this->objPage->pageTitle = strip_tags($stringUtilAdapter->stripInsertTags($this->objEvent->title));
        }

        // Get case
        $this->case = $this->eventRegistration->getRegistrationState($this->objEvent);

        // Dispatch set case event
        $subject = 'Set case event: manipulate case.';
        $event = new GenericEvent(
            $subject,
            [
                'objEvent' => $this->objEvent,
                'moduleInstance' => $this,
            ]
        );

        if ('bookingNotYetPossible' === $this->case) {
            $messageAdapter->addInfo(
                $this->translator->trans(
                    'MSC.bookingNotYetPossible',
                    [$dateAdapter->parse('d.m.Y', $this->objEvent->bookingStartDate)],
                    'contao_default'
                )
            );
        }

        if ('bookingNoLongerPossible' === $this->case) {
            $messageAdapter->addInfo(
                $this->translator->trans(
                    'MSC.bookingNoLongerPossible',
                    [],
                    'contao_default'
                )
            );
        }

        if ('eventFullyBooked' === $this->case) {
            $messageAdapter->addInfo(
                $this->translator->trans(
                    'MSC.eventFullyBooked',
                    [],
                    'contao_default'
                )
            );
        }

        if ('bookingPossible' === $this->case) {
            if ($model->form && null !== ($objFormGeneratorModel = FormModel::findByPk($model->form))) {
                $this->getForm($objFormGeneratorModel);

                if ($this->objForm->validate()) {
                    if ($this->validateEventRegistrationRequest($this->objForm)) {
                        $arrData = [
                            'pid' => $this->objEvent->id,
                            'tstamp' => time(),
                            'addedOn' => time(),
                            'bookingToken' => Uuid::uuid4()->toString(),
                        ];
                        $this->objEventMember->mergeRow($arrData);
                        $this->objEventMember->save();

                        // Dispatch format form data event
                        $subject = 'Format form data event: listen to this event to format/manipulate user input. E.g. convert formatted dates to timestamps, etc.';
                        $event = new GenericEvent(
                            $subject,
                            [
                                'objEvent' => $this->objEvent,
                                'objEventMember' => $this->objEventMember,
                                'objForm' => $this->objForm,
                                'moduleInstance' => $this,
                            ]
                        );
                        $this->eventDispatcher->dispatch(new FormatFormDataEvent($event), FormatFormDataEvent::NAME);
                        // Dispatch post booking event
                        $subject = 'Post booking event: listen to this event to send notifications, to log things, etc.';
                        $event = new GenericEvent(
                            $subject,
                            [
                                'objEvent' => $this->objEvent,
                                'objEventMember' => $this->objEventMember,
                                'objForm' => $this->objForm,
                                'objFormGeneratorModel' => $objFormGeneratorModel,
                                'moduleInstance' => $this,
                            ]
                        );
                        $this->eventDispatcher->dispatch(new PostBookingEvent($event), PostBookingEvent::NAME);

                        // Redirect to the jumpTo page
                        if (null !== $objFormGeneratorModel && $objFormGeneratorModel->jumpTo) {
                            /** @var PageModel $objPageModel */
                            $objPageModel = $pageModelAdapter->findByPk($objFormGeneratorModel->jumpTo);

                            if (null !== $objPageModel) {
                                $strRedirectUrl = $urlAdapter
                                    ->addQueryString(
                                        'bookingToken='.$this->objEventMember->bookingToken,
                                        $objPageModel->getAbsoluteUrl()
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

        $template->case = $this->case;
        $template->countBookings = $template->bookingCount = $this->eventRegistration->getBookingCount($this->objEvent);
        $template->bookingMin = $this->eventRegistration->getBookingMin($this->objEvent);
        $template->event = $this->objEvent;
        $template->user = $this->eventRegistration->getLoggedInFrontendUser();
        $template->model = $model;
        $template->messages = $messageAdapter->hasMessages() ? $messageAdapter->generate('FE') : null;

        return $template->getResponse();
    }

    private function getForm(FormModel $objFormGeneratorModel): Form
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $systemAdapter = $this->framework->getAdapter(System::class);

        $this->objForm = new Form(
            'eventSubscriptionForm',
            'POST',
            static function ($objHaste) use ($inputAdapter) {
                return $inputAdapter->post('FORM_SUBMIT') === $objHaste->getFormId();
            }
        );

        // Bind the event member model to the form input
        $this->objEventMember = new CalendarEventsMemberModel();
        $this->objForm->bindModel($this->objEventMember);

        // Add fields from form generator
        $this->objForm->addFieldsFromFormGenerator(
            $objFormGeneratorModel->id,
            function (&$strField, &$arrDca) use ($systemAdapter) {
                $blnShow = true;

                // Trigger calEvtBookingPostBooking hook
                if (!empty($GLOBALS['TL_HOOKS']['calEvtBookingAddField']) || \is_array($GLOBALS['TL_HOOKS']['calEvtBookingAddField'])) {
                    foreach ($GLOBALS['TL_HOOKS']['calEvtBookingAddField'] as $callback) {
                        $blnShow = $systemAdapter
                            ->importStatic($callback[0])
                            ->{$callback[1]}($this->objForm, $strField, $arrDca, $this->objEvent, $this);

                        if (!$blnShow) {
                            return false;
                        }
                    }
                }

                // Return true otherwise the field will be skipped
                return true;
            }
        );

        return $this->objForm;
    }

    private function validateEventRegistrationRequest(): bool
    {
        // Dispatch validate event registration request event
        $subject = 'Validate event booking request: Check if event is fully booked, if registration deadline has reached, duplicate entries, etc.';
        $event = new GenericEvent(
            $subject,
            [
                'objEvent' => $this->objEvent,
                'objEventMember' => $this->objEventMember,
                'objForm' => $this->objForm,
                'moduleInstance' => $this,
            ]
        );

        $this->eventDispatcher->dispatch(new ValidateEventRegistrationRequestEvent($event), ValidateEventRegistrationRequestEvent::NAME);

        if ($event->isPropagationStopped()) {
            return false;
        }

        return true;
    }
}
