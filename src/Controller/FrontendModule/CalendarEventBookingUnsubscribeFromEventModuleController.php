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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationHelper;
use NotificationCenter\Model\Notification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @FrontendModule(type=CalendarEventBookingUnsubscribeFromEventModuleController::TYPE, category="events", )
 */
class CalendarEventBookingUnsubscribeFromEventModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_unsubscribe_from_event_module';

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var NotificationHelper
     */
    protected $notificationHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CalendarEventsModel
     */
    protected $objEvent;

    /**
     * @var CalendarEventsMemberModel
     */
    protected $objEventMember;

    /**
     * @var PageModel
     */
    protected $objPage;

    /**
     * @var bool
     */
    protected $hasError = false;

    /**
     * @var array
     */
    protected $errorMsg = [];

    /**
     * @var bool
     */
    protected $blnHasUnsubscribed = false;

    public function __construct(ContaoFramework $framework, ScopeMatcher $scopeMatcher, NotificationHelper $notificationHelper, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->notificationHelper = $notificationHelper;
       $this->translator = $translator;
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Is frontend
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->objPage = $page;
            $this->objPage->noSearch = 1;

            /** @var CalendarEventsMemberModel $calendarEventsMemberModelAdapter */
            $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);

            /** @var Controller $controllerAdapter */
            $controllerAdapter = $this->framework->getAdapter(Controller::class);

            if ('true' !== $request->query->get('unsubscribedFromEvent')) {
                $translator = $this->translator;

                $this->objEventMember = $calendarEventsMemberModelAdapter->findOneByBookingToken($request->query->get('bookingToken'));

                if (null === $this->objEventMember) {
                    $this->addError($translator->trans('ERR.invalidBookingToken', [], 'contao_default'));
                }

                if (!$this->hasError) {
                    if (null === ($this->objEvent = $this->objEventMember->getRelated('pid'))) {
                        $this->addError($translator->trans('ERR.eventNotFound', [], 'contao_default'));
                    }
                }

                if (!$this->hasError) {
                    if (!$this->objEvent->enableDeregistration) {
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
                    }
                    // We only have a unsubscription limit expressed in days before event start date
                    else {
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
                    // Delete entry and redirect
                    if ('tl_unsubscribe_from_event' === $request->request->get('FORM_SUBMIT')) {
                        $this->notify($this->objEventMember, $this->objEvent, $model);
                        $this->objEventMember->delete();

                        $href = sprintf(
                            '%s?unsubscribedFromEvent=true&eid=%s',
                            $page->getFrontendUrl(),
                            $this->objEvent->id
                        );

                        $controllerAdapter->redirect($href);
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
        /** @var CalendarEventsModel $calendarEventsModelAdapter */
        $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if ($this->blnHasUnsubscribed) {
            $template->blnHasUnsubscribed = true;

            if (null !== ($objEvent = $calendarEventsModelAdapter->findByPk($request->query->get('eid')))) {
                $template->event = $objEvent;
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
        /** @var StringUtil $stringUtilAdapter */
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);

        /** @var Notification $notificationAdapter */
        $notificationAdapter = $this->framework->getAdapter(Notification::class);

        if ($objEvent->enableDeregistration) {
            // Multiple notifications possible
            $arrNotifications = $stringUtilAdapter->deserialize($model->unsubscribeFromEventNotificationIds);

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
