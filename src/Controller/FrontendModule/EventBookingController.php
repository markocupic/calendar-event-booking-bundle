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

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Date;
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
use Markocupic\CalendarEventBookingBundle\Exception\FormNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(EventBookingController::TYPE, category:'events', template: 'mod_event_booking')]
class EventBookingController extends AbstractFrontendModuleController
{
    public const TYPE = 'event_booking_form';
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
        $this->message = $this->framework->getAdapter(Message::class);
        $this->system = $this->framework->getAdapter(System::class);
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        $this->model = $model;

        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->objPage = $page;

            $showEmpty = true;

            // Get the current event
            // Return an empty string, if...
            // - enableBookingForm isn't set or
            // - event is not published
            if (null !== ($event = EventConfig::getEventFromRequest())) {
                $this->eventConfig = $this->eventFactory->create($event);

                if ($this->eventConfig->get('enableBookingForm') && $this->eventConfig->get('published')) {
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
     * @throws Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $form = FormModel::findByPk($this->model->form);

        if (null === $form) {
            throw new FormNotFoundException('No event booking form assigned to the frontend module. Please check the settings for the frontend module with ID: '.$model->id);
        }

        if (null === $this->eventConfig) {
            throw new PageNotFoundException('Page not found: '.$request->getUri());
        }

        // Load language file
        $this->system->loadLanguageFile($this->eventRegistration->getTable());

        // Get case
        $this->case = $this->eventConfig->getEventStatus();
        $template->caseText = null;

        // Trigger set case hook: manipulate case
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_SET_CASE] as $callback) {
                $this->system->importStatic($callback[0])->{$callback[1]}($this);
            }
        }

        $formSubmitId = $form->formID ? 'auto_'.$form->formID : 'auto_form_'.$form->id;

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

        // If display booking form (regular subscription or subscription to the waiting list is possible)
        if ($this->bookingValidator->validateCanRegister($this->eventConfig)) {
            $this->eventRegistration->setModuleData($model->row());

            // Trigger pre validate hook: e.g. add custom field validators.';
            if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM])) {
                foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_PRE_VALIDATE_BOOKING_FORM] as $callback) {
                    $this->system->importStatic($callback[0])->{$callback[1]}($this);
                }
            }

            $template->form = $this->controller->getForm($model->form);
        }

        // Will not be processed if the form has been submitted and a redirecting has been set on the form.
        $template->case = $this->case;
        $template->model = $this->model;
        $template->messages = $this->message->hasMessages() ? $this->message->generate('FE') : null;

        $this->addTemplateData->addTemplateData($this->eventConfig, $template);

        return $template->getResponse();
    }
}
