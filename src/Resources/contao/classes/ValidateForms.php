<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 13.09.2017
 * Time: 15:34
 */

namespace Markocupic\CalendarEventBookingBundle;

use Contao\Widget;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Email;
use Contao\PageModel;
use Contao\Input;
use Contao\StringUtil;
use Contao\CalendarEventsModel;
use Contao\CalendarEventsMemberModel;
use Contao\Controller;
use Contao\System;
use Haste\Util\Url;
use Psr\Log\LogLevel;


class ValidateForms
{


    /**
     * @param $arrTarget
     */
    public function postUpload($arrTarget)
    {

    }


    /**
     * @param $arrFields
     * @param $formId
     * @param \Form $objForm
     * @return mixed
     */
    public function compileFormFields($arrFields, $formId, $objForm)
    {
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
        if ($arrForm['formID'] == 'event-booking-form')
        {
            if ($objWidget->name == 'escorts')
            {
                $objEvent = CalendarEventsModel::findByIdOrAlias(Input::get('events'));
                if ($objEvent !== null)
                {
                    $maxEscorts = $objEvent->maxEscortsPerMember;
                    if ($maxEscorts > 0)
                    {
                        $opt = array();
                        for ($i = 0; $i <= $maxEscorts; $i++)
                        {
                            $opt[] = array(
                                'value' => $i,
                                'label' => $i
                            );
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
        if ($arrForm['formID'] == 'event-booking-form')
        {
            // Do not auto save anything to the database, this will be done manualy in the processFormData method
            $objForm->storeValues = '';

            if ($objWidget->name == 'email')
            {
                // Check if user is already registered
                if ($objWidget->value != '')
                {

                    $objEvent = CalendarEventsModel::findByIdOrAlias(Input::get('events'));
                    if ($objEvent !== null)
                    {
                        $arrOptions = array(
                            'column' => array('tl_calendar_events_member.email=?', 'tl_calendar_events_member.pid=?'),
                            'value' => array(strtolower($objWidget->value), $objEvent->id)
                        );
                        $objMember = CalendarEventsMemberModel::findAll($arrOptions);
                        if ($objMember !== null)
                        {
                            $errorMsg = sprintf('Eine Anmeldung mit der E-Mail-Adresse "%s" ist bereits eingegangen. Der Anmeldevorgang wurde abgebrochen.', strtolower(Input::post('email')));
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
    public function prepareFormData($arrSubmitted, $arrLabels, $arrFields, $objForm)
    {


    }


    /**
     * @param $arrSet
     * @param $objForm
     * @return mixed
     */
    public function storeFormData($arrSet, $objForm)
    {
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

        if ($arrForm['formID'] == 'event-booking-form')
        {
            $objEvent = CalendarEventsModel::findByIdOrAlias(Input::get('events'));
            if ($objEvent !== null)
            {
                $objModel = new CalendarEventsMemberModel();
                $objModel->setRow($arrSubmitted);
                $objModel->pid = $objEvent->id;
                $objModel->email = strtolower($arrSubmitted['email']);
                $objModel->addedOn = time();
                $objModel->tstamp = time();
                $objModel->save();

                // Add a booking token
                $objModel->bookingToken = md5(sha1(microtime())) . md5(sha1($objModel->email)) . $objModel->id;
                $objModel->save();

                // Send email
                $this->sendEmail($objModel, $objEvent);

                // Log new insert
                $level = LogLevel::INFO;
                $logger = System::getContainer()->get('monolog.logger.contao');
                $strText = 'New booking for event with title "' . $objEvent->title . '"';
                $logger->log($level, $strText, array('contao' => new ContaoContext(__METHOD__, $level)));

                if ($objForm->jumpTo)
                {
                    $objPageModel = PageModel::findByPk($objForm->jumpTo);

                    if ($objPageModel !== null)
                    {
                        // Redirect to the jumpTo page
                        $strRedirectUrl = Url::addQueryString('bookingToken=' . $objModel->bookingToken, $objPageModel->getFrontendUrl());
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
    protected function sendEmail($objEventMember, $objEvent)
    {

        $objEmail = new Email();
        $strBody = html_entity_decode($objEvent->bookingConfirmationEmailBody);

        // Set arrData for Simple Tokens replacing
        $arrData['gender'] = $GLOBALS['TL_LANG']['tl_calendar_events_member'][$objEventMember->gender];
        $arrData['firstname'] = $objEventMember->firstname;
        $arrData['lastname'] = $objEventMember->lastname;
        $arrData['street'] = $objEventMember->street;
        $arrData['postal'] = $objEventMember->postal;
        $arrData['city'] = $objEventMember->city;
        $arrData['phone'] = $objEventMember->phone;
        $arrData['email'] = $objEventMember->email;
        $arrData['street'] = $objEventMember->street;
        $arrData['eventname'] = $objEvent->title;
        $arrData['title'] = $objEvent->title;
        $arrData['escorts'] = $objEventMember->escorts;

        // Replace Simple Tokens
        $strBody = StringUtil::parseSimpleTokens($strBody, $arrData);

        // Send E-Mail
        $objEmail->subject = 'Ihre Anmeldung für ' . $objEvent->title;
        $objEmail->text = $strBody;
        $objEmail->from = $objEvent->emailFrom;
        $objEmail->fromName = $objEvent->emailFromName;
        $objEmail->sendTo($objEventMember->email);

    }
}


