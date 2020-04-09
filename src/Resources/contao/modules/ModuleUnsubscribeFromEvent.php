<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle;

use Contao\BackendTemplate;
use Contao\CalendarEventsMemberModel;
use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\Input;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Patchwork\Utf8;
use NotificationCenter\Model\Notification;

/**
 * Class ModuleUnsubscribeFromEvent
 * @package Markocupic\CalendarEventBookingBundle
 */
class ModuleUnsubscribeFromEvent extends Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_unsubscribefromevent';

    /**
     * @var bool
     */
    protected $hasError = false;

    /**
     * @var array
     */
    protected $errorMsg = [];

    /**
     * @var null
     */
    protected $objEventMember = null;

    /**
     * @var null
     */
    protected $objEvent = null;

    /**
     * @var bool
     */
    protected $blnHasUnsubscribed = false;

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['unsubscribefromevent'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        /** @var PageModel $objPage */
        global $objPage;

        $objPage->noSearch = 1;

        if (Input::get('unsubscribedFromEvent') !== 'true')
        {
            $this->objEventMember = CalendarEventsMemberModel::findBybookingToken(\Input::get('bookingToken'));
            if ($this->objEventMember === null)
            {
                $this->addError($GLOBALS['TL_LANG']['ERR']['invalidBookingToken']);
            }

            if (!$this->hasError)
            {
                if (($this->objEvent = $this->objEventMember->getRelated('pid')) === null)
                {
                    $this->addError($GLOBALS['TL_LANG']['ERR']['eventNotFound']);
                }
            }

            if (!$this->hasError)
            {
                if (!$this->objEvent->enableDeregistration)
                {
                    $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['eventUnsubscriptionNotAllowed'], $this->objEvent->title));
                }
            }

            if (!$this->hasError)
            {
                $limit = !$this->objEvent->unsubscribeLimit > 0 ? 0 : $this->objEvent->unsubscribeLimit;
                if (time() + $limit * 3600 * 24 > $this->objEvent->startDate)
                {
                    $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['unsubscriptionLimitExpired'], $this->objEvent->title));
                }
            }

            if (!$this->hasError)
            {
                // Delete entry and redirect
                if (Input::post('FORM_SUBMIT') === 'tl_unsubscribe_from_event')
                {
                    $this->notify($this->objEventMember, $this->objEvent);
                    $this->objEventMember->delete();

                    global $objPage;
                    PageModel::findByPk($objPage->id);
                    $href = $objPage->getFrontendUrl() . '?unsubscribedFromEvent=true&eid=' . $this->objEvent->id;
                    Controller::redirect($href);
                }
            }
        }

        if (Input::get('unsubscribedFromEvent') === 'true')
        {
            $this->blnHasUnsubscribed = true;
        }

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile()
    {
        if ($this->blnHasUnsubscribed)
        {
            $this->Template->blnHasUnsubscribed = true;
            if (($objEvent = CalendarEventsModel::findByPk(Input::get('eid'))) !== null)
            {
                $this->Template->event = $objEvent->row();
            }
        }
        else
        {
            if ($this->hasError)
            {
                $this->Template->errorMsg = $this->errorMsg;
            }
            else
            {
                $this->Template->formId = 'tl_unsubscribe_from_event';
                $this->Template->event = $this->objEvent->row();
                $this->Template->member = $this->objEventMember->row();
                $this->Template->slabelUnsubscribeFromEvent = $GLOBALS['TL_LANG']['BTN']['slabelUnsubscribeFromEvent'];
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
        if ($objEvent->enableDeregistration)
        {
            // Multiple notifications possible
            $arrNotifications = StringUtil::deserialize($this->unsubscribeFromEventNotificationIds);
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

    /**
     * @param $strMsg
     */
    protected function addError($strMsg)
    {
        $this->hasError = true;
        $this->errorMsg[] = $strMsg;
    }
}
