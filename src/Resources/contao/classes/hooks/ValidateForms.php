<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle;

use Contao\CalendarEventsMemberModel;
use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Date;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\UserModel;
use Contao\Widget;
use Haste\Util\Url;
use NotificationCenter\Model\Notification;
use Psr\Log\LogLevel;

/**
 * Class ValidateForms
 * @package Markocupic\CalendarEventBookingBundle
 */
class ValidateForms
{

    /**
     * @param $arrTarget
     */
    public function postUpload($arrTarget)
    {
        // empty
    }

    /**
     * @param $arrFields
     * @param $formId
     * @param \Form $objForm
     * @return mixed
     */
    public function compileFormFields($arrFields, $formId, $objForm)
    {
        // Do not list input fields under certain conditions
        if ($objForm->isCalendarEventBookingForm)
        {
            $objEvent = CalendarEventsModel::findByIdOrAlias(Input::get('events'));
            if ($objEvent !== null)
            {
                $maxEscorts = $objEvent->maxEscortsPerMember;
                if ($maxEscorts < 1)
                {
                    unset($arrFields['escorts']);
                }
            }
        }

        return $arrFields;
    }

    /**
     * @param Widget $objWidget
     * @param $strForm
     * @param $arrForm
     * @param $objForm
     * @return Widget
     */
    public function loadFormField(Widget $objWidget, $strForm, $arrForm, $objForm)
    {
        if ($objForm->isCalendarEventBookingForm)
        {
            // Convert tstamps to formated date
            if ($objWidget->name === 'dateOfBirth' && $objWidget->value != '')
            {
                if (is_numeric($objWidget->value))
                {
                    $objWidget->value = Date::parse(Config::get('dateFormat'), $objWidget->value);
                    $objWidget->value = Date::parse(Config::get('dateFormat'));
                }
            }

            if ($objWidget->name === 'escorts')
            {
                $objEvent = CalendarEventsModel::findByIdOrAlias(Input::get('events'));
                if ($objEvent !== null)
                {
                    $maxEscorts = $objEvent->maxEscortsPerMember;
                    if ($maxEscorts > 0)
                    {
                        $opt = [];
                        for ($i = 0; $i <= $maxEscorts; $i++)
                        {
                            $opt[] = [
                                'value' => $i,
                                'label' => $i,
                            ];
                        }
                        $objWidget->options = serialize($opt);
                    }
                }
            }
        }

        return $objWidget;
    }

    /**
     * @param Widget $objWidget
     * @param $formId
     * @param $arrForm
     * @param $objForm
     * @return Widget
     */
    public function validateFormField(Widget $objWidget, $formId, $arrForm, $objForm)
    {
        if ($objForm->isCalendarEventBookingForm)
        {
            // Do not auto save anything to the database, this will be done manualy in the processFormData method
            $objForm->storeValues = '';

            // Check if user with submitted email has already booked
            if ($objWidget->name === 'email')
            {
                if ($objWidget->value != '')
                {
                    $objEvent = CalendarEventsModel::findByIdOrAlias(Input::get('events'));
                    if ($objEvent !== null)
                    {
                        if (!$objEvent->enableMultiBookingWithSameAddress)
                        {
                            $arrOptions = [
                                'column' => ['tl_calendar_events_member.email=?', 'tl_calendar_events_member.pid=?'],
                                'value'  => [strtolower($objWidget->value), $objEvent->id],
                            ];
                            $objMember = CalendarEventsMemberModel::findAll($arrOptions);
                            if ($objMember !== null)
                            {
                                $errorMsg = sprintf($GLOBALS['TL_LANG']['MSC']['youHaveAlreadyBooked'], strtolower(Input::post('email')));
                                $objWidget->addError($errorMsg);
                            }
                        }
                    }
                }
            }

            // Check maxEscortsPerMember
            if ($objWidget->name === 'escorts')
            {
                if ($objWidget->value < 0)
                {
                    $errorMsg = sprintf($GLOBALS['TL_LANG']['MSC']['enterPosIntVal']);
                    $objWidget->addError($errorMsg);
                }
                elseif ($objWidget->value > 0)
                {
                    $objEvent = CalendarEventsModel::findByIdOrAlias(Input::get('events'));
                    if ($objEvent !== null)
                    {
                        if ($objWidget->value > $objEvent->maxEscortsPerMember)
                        {
                            $errorMsg = sprintf($GLOBALS['TL_LANG']['MSC']['maxEscortsPossible'], $objEvent->maxEscortsPerMember);
                            $objWidget->addError($errorMsg);
                        }
                    }
                }
            }
        }

        return $objWidget;
    }

    /**
     * @param $arrSubmitted
     * @param $arrLabels
     * @param $arrFields
     * @param $objForm
     */
    public function prepareFormData(&$arrSubmitted, $arrLabels, $arrFields, $objForm)
    {
        if ($objForm->isCalendarEventBookingForm)
        {
            if ($arrSubmitted['dateOfBirth'] != '')
            {
                $tstamp = strtotime($arrSubmitted['dateOfBirth']);
                if ($tstamp !== false)
                {
                    $arrSubmitted['dateOfBirth'] = $tstamp;
                }
            }
        }
    }

    /**
     * @param $arrSet
     * @param $objForm
     * @return mixed
     */
    public function storeFormData($arrSet, $objForm)
    {
        // empty
        return $arrSet;
    }

    /**
     * @param $arrSubmitted
     * @param $arrForm
     * @param $arrFiles
     * @param $arrLabels
     * @param $objForm
     */
    public function processFormData($arrSubmitted, $arrForm, $arrFiles, $arrLabels, $objForm)
    {
        if ($objForm->isCalendarEventBookingForm)
        {
            $objEvent = CalendarEventsModel::findByIdOrAlias(Input::get('events'));
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
                $level = LogLevel::INFO;
                $logger = System::getContainer()->get('monolog.logger.contao');
                $strText = 'New booking for event with title "' . $objEvent->title . '"';
                $logger->log($level, $strText, ['contao' => new ContaoContext(__METHOD__, $level)]);

                if ($objForm->jumpTo)
                {
                    $objPageModel = PageModel::findByPk($objForm->jumpTo);

                    if ($objPageModel !== null)
                    {
                        // Redirect to the jumpTo page
                        $strRedirectUrl = Url::addQueryString('bookingToken=' . $objCalendarEventsMemberModel->bookingToken, $objPageModel->getFrontendUrl());
                        Controller::redirect($strRedirectUrl);
                    }
                }
            }
        }
    }

    /**
     * @param $objEventMember
     * @param $objEvent
     */
    protected function notify($objEventMember, $objEvent)
    {
        global $objPage;
        if ($objEvent->enableNotificationCenter)
        {
            // Multiple notifications possible
            $arrNotifications = StringUtil::deserialize($objEvent->eventBookingNotificationCenterIds);
            if (!empty($arrNotifications) && is_array($arrNotifications))
            {
                // Get $arrToken from helper
                $arrTokens = NotificationHelper::getNotificationTokens($objEventMember, $objEvent);

                // Send notification (multiple notifications possible)
                foreach ($arrNotifications as $notificationId)
                {
                    $objNotification = Notification::findByPk($notificationId);
                    if ($objNotification !== null)
                    {
                        $objNotification->send($arrTokens, $objPage->language);
                    }
                }
            }
        }
    }
}
