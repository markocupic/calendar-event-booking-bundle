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
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventBooking\Notification\Notification;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(CalendarEventBookingUnsubscribeFromEventModuleController::TYPE, category:'events', template: 'mod_calendar_event_booking_unsubscribe_from_event_module')]
class CalendarEventBookingUnsubscribeFromEventModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_unsubscribe_from_event_module';

    protected CalendarEventsModel|null $objEvent = null;
    protected PageModel|null $objPage = null;
    protected bool $blnHasUnsubscribed = false;
    protected bool $hasError = false;
    protected array $errorMsg = [];

    // Adapters
    private Adapter $calendarEvents;
    private Adapter $controller;
    private Adapter $eventMember;
    private Adapter $stringUtil;
    private Adapter $system;

    public function __construct(
        public ContaoFramework $framework,
        public ScopeMatcher $scopeMatcher,
        public TranslatorInterface $translator,
        private readonly Notification $notification,
        private readonly EventFactory $eventFactory,
        private EventRegistration $eventRegistration,
    ) {
        $this->calendarEvents = $this->framework->getAdapter(CalendarEventsModel::class);
        $this->controller = $this->framework->getAdapter(Controller::class);
        $this->eventMember = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $this->stringUtil = $this->framework->getAdapter(StringUtil::class);
        $this->system = $this->framework->getAdapter(System::class);
    }

    /**
     * @throws \Exception
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Is frontend
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->objPage = $page;
            $this->objPage->noSearch = 1;

            if ('true' !== $request->query->get('unsubscribedFromEvent')) {
                $translator = $this->translator;

                $bookingToken = $request->query->get('bookingToken', false);

                if (!$this->hasError) {
                    if (empty($bookingToken)) {
                        $this->addError($translator->trans('ERR.invalid_booking_token', [], 'contao_default'));
                    }
                }

                $eventMember = $this->eventMember->findOneByBookingToken($bookingToken);

                if (!$this->hasError) {
                    if (null === $eventMember) {
                        $this->addError($translator->trans('ERR.invalid_booking_token', [], 'contao_default'));
                    }
                }

                $this->eventRegistration->setModel($eventMember);

                if (!$this->hasError) {
                    if (null === ($this->objEvent = $this->eventRegistration->getModel()->getRelated('pid'))) {
                        $this->addError($translator->trans('ERR.event_not_found', [], 'contao_default'));
                    }
                }

                if (!$this->hasError) {
                    if (BookingState::STATE_UNSUBSCRIBED === $this->eventRegistration->getModel()->bookingState) {
                        $this->addError($translator->trans('ERR.already_unsubscribed.', [$this->objEvent->title], 'contao_default'));
                    }
                }

                if (!$this->hasError) {
                    if (!$this->objEvent->activateDeregistration || (!empty($this->eventRegistration->getModel()->bookingState) && BookingState::STATE_CONFIRMED !== $this->eventRegistration->getModel()->bookingState)) {
                        $this->addError($translator->trans('ERR.event_unsubscription_not_allowed', [$this->objEvent->title], 'contao_default'));
                    }
                }

                if (!$this->hasError) {
                    if (BookingState::STATE_WAITING_LIST !== $this->eventRegistration->getModel()->bookingState && BookingState::STATE_CONFIRMED !== $this->eventRegistration->getModel()->bookingState && BookingState::STATE_NOT_CONFIRMED !== $this->eventRegistration->getModel()->bookingState) {
                        $this->addError($translator->trans('ERR.event_unsubscription_not_allowed', [$this->objEvent->title], 'contao_default'));
                    }
                }

                if (!$this->hasError) {
                    $blnLimitExpired = false;

                    // User has set a specific unsubscription limit timestamp, this has precedence
                    if (!empty($this->objEvent->unsubscribeLimitTstamp)) {
                        if (time() > $this->objEvent->unsubscribeLimitTstamp) {
                            $blnLimitExpired = true;
                        }
                    } else {
                        // We only have an unsubscription limit expressed in days before event start date
                        $limit = !$this->objEvent->unsubscribeLimit > 0 ? 0 : $this->objEvent->unsubscribeLimit;

                        if (time() + $limit * 3600 * 24 > $this->objEvent->startDate) {
                            $blnLimitExpired = true;
                        }
                    }

                    if ($blnLimitExpired) {
                        $this->addError($translator->trans('ERR.unsubscription_limit_expired', [$this->objEvent->title], 'contao_default'));
                    }
                }

                if (!$this->hasError) {
                    // Delete record, notify and redirect
                    if ('tl_unsubscribe_from_event' === $request->request->get('FORM_SUBMIT')) {
                        // Unsubscribe member
                        $this->eventRegistration->unsubscribe();

                        $eventConfig = $this->eventFactory->create($this->objEvent);

                        // Trigger the unsubscribe from event hook
                        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_UNSUBSCRIBE_FROM_EVENT]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_UNSUBSCRIBE_FROM_EVENT])) {
                            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_UNSUBSCRIBE_FROM_EVENT] as $callback) {
                                $this->system->importStatic($callback[0])->{$callback[1]}($eventConfig, $this->eventRegistration);
                            }
                        }

                        $href = sprintf(
                            '%s?unsubscribedFromEvent=true&eid=%s',
                            $page->getFrontendUrl(),
                            $this->objEvent->id
                        );

                        $this->controller->redirect($href);
                    }
                }
            }

            if ('true' === $request->query->get('unsubscribedFromEvent')) {
                $this->blnHasUnsubscribed = true;
            }
        }

        // Call the parent method
        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * @throws \Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response|null
    {
        if ($this->blnHasUnsubscribed) {
            $template->blnHasUnsubscribed = true;

            if (null !== ($objEvent = $this->calendarEvents->findByPk($request->query->get('eid')))) {
                $template->event = $objEvent;
                $template->eventConfig = $this->eventFactory->create($objEvent);
            }
        } else {
            $template->blnHasUnsubscribed = false;

            if (!$this->hasError) {
                $template->formId = 'tl_unsubscribe_from_event';
                $template->event = $this->objEvent;
                $template->member = $this->eventRegistration->getModel();
            }
        }

        $template->hasError = $this->hasError;
        $template->errorMsg = $this->errorMsg;

        return $template->getResponse();
    }

    /**
     * @throws \Exception
     */
    protected function sendNotifications(CalendarEventsMemberModel $eventMember, CalendarEventsModel $objEvent, ModuleModel $model): void
    {
        // Multiple notifications possible
        $arrNotificationIds = $this->stringUtil->deserialize($objEvent->eventUnsubscribeNotification, true);

        if (!empty($arrNotificationIds)) {
            // Get notification tokens
            $eventConfig = $this->eventFactory->create($objEvent);

            $this->notification->setTokens($eventConfig, $eventMember, (int) $eventConfig->getModel()->eventUnsubscribeNotificationSender);
            $this->notification->notify($arrNotificationIds);
        }
    }

    protected function addError(string $strMsg): void
    {
        $this->hasError = true;
        $this->errorMsg[] = $strMsg;
    }
}
