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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks;

use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Form;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Haste\Util\Url;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationHelper;
use NotificationCenter\Model\Notification;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Class ProcessFormData.
 */
class ProcessFormData
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ProcessFormData constructor.
     */
    public function __construct(ContaoFramework $framework, NotificationHelper $notificationHelper, LoggerInterface $logger = null)
    {
        $this->framework = $framework;
        $this->notificationHelper = $notificationHelper;
        $this->logger = $logger;
    }

    public function processFormData(array $arrSubmitted, array $arrForm, ?array $arrFiles, array $arrLabels, Form $objForm): void
    {
        if ($objForm->isCalendarEventBookingForm) {
            /** @var CalendarEventsModel $calendarEventsModelAdapter */
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

            /** @var PageModel $pageModelAdapter */
            $pageModelAdapter = $this->framework->getAdapter(PageModel::class);

            /** @var Controller $controllerAdapter */
            $controllerAdapter = $this->framework->getAdapter(Controller::class);

            /** @var Url $urlAdapter */
            $urlAdapter = $this->framework->getAdapter(Url::class);

            /** @var System $systemAdapter */
            $systemAdapter = $this->framework->getAdapter(System::class);

            $objEvent = $calendarEventsModelAdapter->findByIdOrAlias(Input::get('events'));

            if (null !== $objEvent) {
                $objCalendarEventsMemberModel = new CalendarEventsMemberModel();
                $objCalendarEventsMemberModel->setRow($arrSubmitted);
                $objCalendarEventsMemberModel->escorts = $objCalendarEventsMemberModel->escorts > 0 ? $objCalendarEventsMemberModel->escorts : 0;
                $objCalendarEventsMemberModel->pid = $objEvent->id;
                $objCalendarEventsMemberModel->email = strtolower($arrSubmitted['email']);
                $objCalendarEventsMemberModel->addedOn = time();
                $objCalendarEventsMemberModel->tstamp = time();
                $objCalendarEventsMemberModel->save();

                // Add a booking token
                $objCalendarEventsMemberModel->bookingToken = md5(sha1(microtime())).md5(sha1($objCalendarEventsMemberModel->email)).$objCalendarEventsMemberModel->id;
                $objCalendarEventsMemberModel->save();

                // Trigger calEvtBookingPostBooking hook
                if (!empty($GLOBALS['TL_HOOKS']['calEvtBookingPostBooking']) || \is_array($GLOBALS['TL_HOOKS']['calEvtBookingPostBooking'])) {
                    foreach ($GLOBALS['TL_HOOKS']['calEvtBookingPostBooking'] as $callback) {
                        $systemAdapter
                            ->importStatic($callback[0])
                            ->{$callback[1]}($arrSubmitted, $arrForm, $arrFiles, $arrLabels, $objForm, $objEvent, $objCalendarEventsMemberModel)
                        ;
                    }
                }

                // Send notification
                $this->notify($objCalendarEventsMemberModel, $objEvent);

                // Log new insert
                if (null !== $this->logger) {
                    $level = LogLevel::INFO;
                    $strText = 'New booking for event with title "'.$objEvent->title.'"';
                    $this->logger->log(
                        $level,
                        $strText, [
                            'contao' => new ContaoContext(__METHOD__, $level),
                        ]);
                }

                if ($objForm->jumpTo) {
                    $objPageModel = $pageModelAdapter->findByPk($objForm->jumpTo);

                    if (null !== $objPageModel) {
                        // Redirect to the jumpTo page
                        $strRedirectUrl = $urlAdapter->addQueryString('bookingToken='.$objCalendarEventsMemberModel->bookingToken, $objPageModel->getFrontendUrl());
                        $controllerAdapter->redirect($strRedirectUrl);
                    }
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function notify(CalendarEventsMemberModel $objEventMember, CalendarEventsModel $objEvent): void
    {
        global $objPage;

        /** @var Notification $notificationAdapter */
        $notificationAdapter = $this->framework->getAdapter(Notification::class);

        /** @var StringUtil $stringUtilAdaper */
        $stringUtilAdaper = $this->framework->getAdapter(StringUtil::class);

        if ($objEvent->enableNotificationCenter) {
            // Multiple notifications possible
            $arrNotifications = $stringUtilAdaper->deserialize($objEvent->eventBookingNotificationCenterIds);

            if (!empty($arrNotifications) && \is_array($arrNotifications)) {
                // Get $arrToken from helper
                $arrTokens = $this->notificationHelper->getNotificationTokens($objEventMember, $objEvent);

                // Send notification (multiple notifications possible)
                foreach ($arrNotifications as $notificationId) {
                    $objNotification = $notificationAdapter->findByPk($notificationId);

                    if (null !== $objNotification) {
                        $objNotification->send($arrTokens, $objPage->language);
                    }
                }
            }
        }
    }
}
