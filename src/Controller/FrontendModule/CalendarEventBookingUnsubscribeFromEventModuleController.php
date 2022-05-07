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
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\Helper\NotificationHelper;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use NotificationCenter\Model\Notification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @FrontendModule(type=CalendarEventBookingUnsubscribeFromEventModuleController::TYPE, category="events")
 */
class CalendarEventBookingUnsubscribeFromEventModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_unsubscribe_from_event_module';

    public ContaoFramework $framework;
    public ScopeMatcher $scopeMatcher;
    public TranslatorInterface $translator;
    protected NotificationHelper $notificationHelper;
    protected EventFactory $eventFactory;

    protected ?CalendarEventsModel $objEvent = null;
    protected ?CalendarEventsMemberModel $objEventMember = null;
    protected ?PageModel $objPage = null;
    protected bool $blnHasUnsubscribed = false;
    protected bool $hasError = false;
    protected array $errorMsg = [];

    // Adapters
    // Adapters
    private Adapter $controller;
    private Adapter $eventMember;

    public function __construct(ContaoFramework $framework, ScopeMatcher $scopeMatcher, NotificationHelper $notificationHelper, TranslatorInterface $translator, EventFactory $eventFactory)
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->notificationHelper = $notificationHelper;
        $this->translator = $translator;
        $this->eventFactory = $eventFactory;

        $this->eventMember = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $this->controller = $this->framework->getAdapter(Controller::class);
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

                $this->objEventMember = $this->eventMember->findOneByBookingToken($request->query->get('bookingToken'));

                if (null === $this->objEventMember) {
                    $this->addError($translator->trans('ERR.invalidBookingToken', [], 'contao_default'));
                }

                if (!$this->hasError) {
                    if (null === ($this->objEvent = $this->objEventMember->getRelated('pid'))) {
                        $this->addError($translator->trans('ERR.eventNotFound', [], 'contao_default'));
                    }
                }

                if (!$this->hasError) {
                    if (!$this->objEvent->activateDeregistration) {
                        $this->addError($translator->trans('ERR.eventUnsubscriptionNotAllowed', [$this->objEvent->title], 'contao_default'));
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
                        $this->addError($translator->trans('ERR.unsubscriptionLimitExpired', [$this->objEvent->title], 'contao_default'));
                    }
                }

                if (!$this->hasError) {
                    // Delete record, notify and redirect
                    if ('tl_unsubscribe_from_event' === $request->request->get('FORM_SUBMIT')) {
                        // Set booking state
                        $this->objEventMember->bookingState = BookingState::STATE_UNSUBSCRIBED;
                        $this->objEventMember->save();

                        // Send notifications
                        $this->notify($this->objEventMember, $this->objEvent, $model);

                        // Trigger the unsubscribe from event hook
                        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_UNSUBSCRIBE_FROM_EVENT]) && \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_UNSUBSCRIBE_FROM_EVENT])) {
                            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_UNSUBSCRIBE_FROM_EVENT] as $callback) {
                                $this->system->importStatic($callback[0])->{$callback[1]}($this);
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

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if ($this->blnHasUnsubscribed) {
            $template->blnHasUnsubscribed = true;

            if (null !== ($objEvent = $calendarEventsModelAdapter->findByPk($request->query->get('eid')))) {
                $template->event = $objEvent;
                $template->eventConfig = $this->eventFactory->create($objEvent);
            }
        } else {
            if ($this->hasError) {
                $template->errorMsg = $this->errorMsg;
            } else {
                $template->formId = 'tl_unsubscribe_from_event';
                $template->event = $this->objEvent;
                $template->member = $this->objEventMember;
            }
        }

        return $template->getResponse();
    }

    /**
     * @throws \Exception
     */
    protected function notify(CalendarEventsMemberModel $objEventMember, CalendarEventsModel $objEvent, ModuleModel $model): void
    {
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        $notificationAdapter = $this->framework->getAdapter(Notification::class);

        if ($objEvent->activateDeregistration) {
            // Multiple notifications possible
            $arrNotifications = $stringUtilAdapter->deserialize($model->cebb_unsubscribeNotification);

            if (!empty($arrNotifications) && \is_array($arrNotifications)) {
                // Get $arrToken from helper
                $arrTokens = $this->notificationHelper->getNotificationTokens($objEventMember);

                // Send notification (multiple notifications possible)
                foreach ($arrNotifications as $notificationId) {
                    $objNotification = $notificationAdapter->findByPk($notificationId);

                    if (null !== $objNotification) {
                        $objNotification->send($arrTokens, $this->objPage->language);
                    }
                }
            }
        }
    }

    protected function addError(string $strMsg): void
    {
        $this->hasError = true;
        $this->errorMsg[] = $strMsg;
    }
}
