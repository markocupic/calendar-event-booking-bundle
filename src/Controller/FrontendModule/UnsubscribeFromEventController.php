<?php

declare(strict_types=1);

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsMemberModel;
use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationHelper;
use NotificationCenter\Model\Notification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;

/**
 * Class UnsubscribeFromEventController
 * @package Markocupic\CalendarEventBookingBundle\Controller\FrontendModule
 * @FrontendModule(category="events", type="unsubscribefromevent")
 */
class UnsubscribeFromEventController extends AbstractFrontendModuleController
{

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var NotificationHelper
     */
    protected $notificationHelper;

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

    /**
     * UnsubscribeFromEventController constructor.
     * @param RequestStack $requestStack
     * @param NotificationHelper $notificationHelper
     */
    public function __construct(RequestStack $requestStack, NotificationHelper $notificationHelper)
    {
        $this->requestStack = $requestStack;
        $this->notificationHelper = $notificationHelper;
    }

    /**
     * @param Request $request
     * @param ModuleModel $model
     * @param string $section
     * @param array|null $classes
     * @param PageModel|null $page
     * @return Response
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Return empty string, if user is not logged in as a frontend user
        if ($this->isFrontend())
        {
            $this->objPage = $page;
            $this->objPage->noSearch = 1;

            /** @var Input $configAdapter */
            $inputAdapter = $this->get('contao.framework')->getAdapter(Input::class);

            /** @var CalendarEventsMemberModel $calendarEventsMemberModelAdapter */
            $calendarEventsMemberModelAdapter = $this->get('contao.framework')->getAdapter(CalendarEventsMemberModel::class);

            /** @var Controller $controllerAdapter */
            $controllerAdapter = $this->get('contao.framework')->getAdapter(Controller::class);

            if ($inputAdapter->get('unsubscribedFromEvent') !== 'true')
            {
                $translator = $this->get('translator');

                $this->objEventMember = $calendarEventsMemberModelAdapter->findOneByBookingToken($inputAdapter->get('bookingToken'));
                if ($this->objEventMember === null)
                {
                    $this->addError($translator->trans('ERR.invalidBookingToken', [], 'contao_default'));
                }

                if (!$this->hasError)
                {
                    if (($this->objEvent = $this->objEventMember->getRelated('pid')) === null)
                    {
                        $this->addError($translator->trans('ERR.eventNotFound', [], 'contao_default'));
                    }
                }

                if (!$this->hasError)
                {
                    if (!$this->objEvent->enableDeregistration)
                    {
                        $this->addError($translator->trans('ERR.eventUnsubscriptionNotAllowed', [$this->objEvent->title], 'contao_default'));
                    }
                }

                if (!$this->hasError)
                {
                    $limit = !$this->objEvent->unsubscribeLimit > 0 ? 0 : $this->objEvent->unsubscribeLimit;
                    if (time() + $limit * 3600 * 24 > $this->objEvent->startDate)
                    {
                        $this->addError($translator->trans('ERR.unsubscriptionLimitExpired', [$this->objEvent->title], 'contao_default'));
                    }
                }

                if (!$this->hasError)
                {
                    // Delete entry and redirect
                    if ($inputAdapter->post('FORM_SUBMIT') === 'tl_unsubscribe_from_event')
                    {
                        $this->notify($this->objEventMember, $this->objEvent, $model);
                        $this->objEventMember->delete();

                        $href = $page->getFrontendUrl() . '?unsubscribedFromEvent=true&eid=' . $this->objEvent->id;
                        $controllerAdapter->redirect($href);
                    }
                }
            }

            if ($inputAdapter->get('unsubscribedFromEvent') === 'true')
            {
                $this->blnHasUnsubscribed = true;
            }
        }

        // Call the parent method
        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['contao.framework'] = ContaoFramework::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    /**
     * @param Template $template
     * @param ModuleModel $model
     * @param Request $request
     * @return null|Response
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        /** @var CalendarEventsModel $calendarEventsModelAdapter */
        $calendarEventsModelAdapter = $this->get('contao.framework')->getAdapter(CalendarEventsModel::class);

        /** @var Input $inputAdapter */
        $inputAdaper = $this->get('contao.framework')->getAdapter(Input::class);

        if ($this->blnHasUnsubscribed)
        {
            $template->blnHasUnsubscribed = true;
            if (($objEvent = $calendarEventsModelAdapter->findByPk($inputAdaper->get('eid'))) !== null)
            {
                $template->event = $objEvent->row();
            }
        }
        else
        {
            if ($this->hasError)
            {
                $template->errorMsg = $this->errorMsg;
            }
            else
            {
                $translator = $this->get('translator');
                $template->formId = 'tl_unsubscribe_from_event';
                $template->event = $this->objEvent->row();
                $template->member = $this->objEventMember->row();
                $template->slabelUnsubscribeFromEvent = $translator->trans('BTN.slabelUnsubscribeFromEvent', [], 'contao_default');
            }
        }

        return $template->getResponse();
    }

    /**
     * @param CalendarEventsMemberModel $objEventMember
     * @param CalendarEventsModel $objEvent
     * @param ModuleModel $model
     * @throws \Exception
     */
    protected function notify(CalendarEventsMemberModel $objEventMember, CalendarEventsModel $objEvent, ModuleModel $model): void
    {
        /** @var StringUtil $stringUtilAdapter */
        $stringUtilAdapter = $this->get('contao.framework')->getAdapter(StringUtil::class);

        /** @var Notification $notificationAdapter */
        $notificationAdapter = $this->get('contao.framework')->getAdapter(Notification::class);

        if ($objEvent->enableDeregistration)
        {
            // Multiple notifications possible
            $arrNotifications = $stringUtilAdapter->deserialize($model->unsubscribeFromEventNotificationIds);
            if (!empty($arrNotifications) && is_array($arrNotifications))
            {
                // Get $arrToken from helper
                $arrTokens = $this->notificationHelper->getNotificationTokens($objEventMember, $objEvent);

                // Send notification (multiple notifications possible)
                foreach ($arrNotifications as $notificationId)
                {
                    $objNotification = $notificationAdapter->findByPk($notificationId);
                    if ($objNotification !== null)
                    {
                        $objNotification->send($arrTokens, $this->objPage->language);
                    }
                }
            }
        }
    }

    /**
     * @param string $strMsg
     */
    protected function addError(string $strMsg): void
    {
        $this->hasError = true;
        $this->errorMsg[] = $strMsg;
    }

    /**
     * Identify the Contao scope (TL_MODE) of the current request
     * @return bool
     */
    protected function isFrontend(): bool
    {
        return $this->get('contao.routing.scope_matcher')->isFrontendRequest($this->requestStack->getCurrentRequest());
    }
}
