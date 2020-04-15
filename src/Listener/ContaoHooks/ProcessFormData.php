<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsMemberModel;
use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Form;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Haste\Util\Url;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationHelper;
use NotificationCenter\Model\Notification;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Class ProcessFormData
 * @package Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks
 */
class ProcessFormData
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * ProcessFormData constructor.
     * @param ContaoFramework $framework
     * @param LoggerInterface|null $logger
     * @param NotificationHelper $notificationHelper
     */
    public function __construct(ContaoFramework $framework, LoggerInterface $logger = null, NotificationHelper $notificationHelper)
    {
        $this->framework = $framework;
        $this->logger = $logger;
        $this->notificationHelper = $notificationHelper;
    }

    /**
     * @param array $arrSubmitted
     * @param array $arrForm
     * @param array|null $arrFiles
     * @param array $arrLabels
     * @param Form $objForm
     */
    public function processFormData(array $arrSubmitted, array $arrForm, ?array $arrFiles, array $arrLabels, Form $objForm): void
    {
        if ($objForm->isCalendarEventBookingForm)
        {
            /** @var CalendarEventsModel $calendarEventsModelAdapter */
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            /** @var PageModel $pageModelAdapter */
            $pageModelAdapter = $this->framework->getAdapter(PageModel::class);

            /** @var Controller $controllerAdapter */
            $controllerAdapter = $this->framework->getAdapter(Controller::class);

            /** @var Url $urlAdapter */
            $urlAdapter = $this->framework->getAdapter(Url::class);

            $objEvent = $calendarEventsModelAdapter->findByIdOrAlias(Input::get('events'));
            if ($objEvent !== null)
            {
                $objCalendarEventsMemberModel = new CalendarEventsMemberModel();
                $objCalendarEventsMemberModel->setRow($arrSubmitted);
                $objCalendarEventsMemberModel->escorts = $objCalendarEventsMemberModel->escorts > 0 ? $objCalendarEventsMemberModel->escorts : 0;
                $objCalendarEventsMemberModel->pid = $objEvent->id;
                $objCalendarEventsMemberModel->email = strtolower($arrSubmitted['email']);
                $objCalendarEventsMemberModel->addedOn = time();
                $objCalendarEventsMemberModel->tstamp = time();
                $objCalendarEventsMemberModel->save();

                // Add a booking token
                $objCalendarEventsMemberModel->bookingToken = md5(sha1(microtime())) . md5(sha1($objCalendarEventsMemberModel->email)) . $objCalendarEventsMemberModel->id;
                $objCalendarEventsMemberModel->save();

                // Send notification
                $this->notify($objCalendarEventsMemberModel, $objEvent);

                // Log new insert
                if ($this->logger !== null)
                {
                    $level = LogLevel::INFO;
                    $strText = 'New booking for event with title "' . $objEvent->title . '"';
                    $this->logger->log(
                        $level,
                        $strText, [
                        'contao' => new ContaoContext(__METHOD__, $level)
                    ]);
                }

                if ($objForm->jumpTo)
                {
                    $objPageModel = $pageModelAdapter->findByPk($objForm->jumpTo);

                    if ($objPageModel !== null)
                    {
                        // Redirect to the jumpTo page
                        $strRedirectUrl = $urlAdapter->addQueryString('bookingToken=' . $objCalendarEventsMemberModel->bookingToken, $objPageModel->getFrontendUrl());
                        $controllerAdapter->redirect($strRedirectUrl);
                    }
                }
            }
        }
    }

    /**
     * @param CalendarEventsMemberModel $objEventMember
     * @param CalendarEventsModel $objEvent
     * @throws \Exception
     */
    protected function notify(CalendarEventsMemberModel $objEventMember, CalendarEventsModel $objEvent): void
    {
        global $objPage;

        /** @var Notification $notificationAdapter */
        $notificationAdapter = $this->framework->getAdapter(Notification::class);

        /** @var StringUtil $stringUtilAdaper */
        $stringUtilAdaper = $this->framework->getAdapter(StringUtil::class);

        if ($objEvent->enableNotificationCenter)
        {
            // Multiple notifications possible
            $arrNotifications = $stringUtilAdaper->deserialize($objEvent->eventBookingNotificationCenterIds);
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
                        $objNotification->send($arrTokens, $objPage->language);
                    }
                }
            }
        }
    }

}
