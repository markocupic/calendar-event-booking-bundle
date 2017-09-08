<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Markocupic\CalendarEventBookingBundle;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Email;
use Contao\PageModel;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Module;
use Contao\Input;
use Contao\Environment;
use Contao\Config;
use Contao\StringUtil;
use Contao\CalendarEventsModel;
use Contao\CalendarEventsMemberModel;
use Contao\Controller;
use Contao\BackendTemplate;
use Contao\System;
use Patchwork\Utf8;
use Haste\Form\Form;
use Psr\Log\LogLevel;

/**
 * Class ModuleEventBooking
 * @package Markocupic\CalendarEventBookingBundle
 */
class ModuleEventBooking extends Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_eventbooking';


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

            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['eventbooking'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['events']) && Config::get('useAutoItem') && isset($_GET['auto_item']))
        {
            Input::setGet('events', Input::get('auto_item'));
        }

        // Do not index or cache the page if no event has been specified
        if (!Input::get('events'))
        {
            /** @var PageModel $objPage */
            global $objPage;

            $objPage->noSearch = 1;
            $objPage->cache = 0;

            return '';
        }

        // Get the current event && return empty string if addBookingForm isn't set
        $objEvent = CalendarEventsModel::findByIdOrAlias(\Input::get('events'));
        if ($objEvent !== null)
        {
            if (!$objEvent->addBookingForm)
            {
                return '';
            }
        }


        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        global $objPage;
        Controller::loadLanguageFile('tl_calendar_events_member');

        $this->Template->event = '';
        $this->Template->referer = 'javascript:history.go(-1)';
        $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

        // Get the current event
        $objEvent = CalendarEventsModel::findByIdOrAlias(\Input::get('events'));

        if (null === $objEvent)
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        // Overwrite the page title (see #2853 and #4955)
        if ($objEvent->title != '')
        {
            $objPage->pageTitle = strip_tags(StringUtil::stripInsertTags($objEvent->title));
        }

        $this->Template->setData($objEvent->row());
        $this->Template->id = $this->id;


        $case = 'bookingPossible';
        if ($objEvent->bookingStartDate > 0 && $objEvent->bookingStartDate > time())
        {
            // User has to wait. Booking is not possible yet
            $case = 'bookingNotYetPossible';
        }
        elseif ($objEvent->bookingEndDate > 0 && $objEvent->bookingEndDate < time())
        {
            // User is to late the sign in deadline has proceeded
            $case = 'bookingNoLongerPossible';
        }
        else
        {
            // Check if event is not fully booked
            $countBookings = CalendarEventsMemberModel::countBy('pid', $objEvent->id);
            if ($countBookings > 0)
            {
                if ($objEvent->maxMembers > 0 && $countBookings >= $objEvent->maxMembers)
                {
                    $case = 'eventFullyBooked';
                }
            }
        }

        $this->Template->case = $case;


        switch ($case)
        {
            case 'bookingPossible':
                $strForm = $this->generateForm($objEvent);
                $this->Template->form = $strForm;
                break;
            case 'bookingNotYetPossible':
                break;
            case 'bookingNoLongerPossible':
                break;
            case 'eventFullyBooked':
                break;

        }


    }

    /**
     * @param $objEvent
     * @return string
     */
    protected function generateForm($objEvent)
    {
        // First param is the form id
        // Second is either GET or POST
        // Third is a callable that decides when your form is submitted
        // You can pass an optional fourth parameter (true by default) to turn the form into a table based one
        $objForm = new Form('eventbooking', 'POST', function ($objHaste)
        {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });
        $objForm->setFormActionFromUri(Environment::get('uri'));


        // Now let's add form fields:
        // Now let's add form fields:
        $objForm->addFormField('gender', array(
            'label' => 'Anrede',
            'inputType' => 'select',
            'reference' => &$GLOBALS['TL_LANG']['tl_calendar_events_member'],
            'options' => array('male', 'female'),
            'eval' => array('includeBlankOption' => true, 'mandatory' => true)
        ));
        $objForm->addFormField('firstname', array(
            'label' => 'Vorname',
            'inputType' => 'text',
            'eval' => array('mandatory' => true)
        ));
        $objForm->addFormField('lastname', array(
            'label' => 'Nachname',
            'inputType' => 'text',
            'eval' => array('mandatory' => true)
        ));
        $objForm->addFormField('street', array(
            'label' => 'Strasse',
            'inputType' => 'text',
            'eval' => array('mandatory' => true)
        ));
        $objForm->addFormField('postal', array(
            'label' => 'Postleitzahl',
            'inputType' => 'text',
            'eval' => array('mandatory' => true)
        ));
        $objForm->addFormField('city', array(
            'label' => 'Ort',
            'inputType' => 'text',
            'eval' => array('mandatory' => true)
        ));
        $objForm->addFormField('phone', array(
            'label' => 'Telefon',
            'inputType' => 'text',
            'eval' => array('mandatory' => true)
        ));
        $objForm->addFormField('email', array(
            'label' => 'E-Mail-Adresse',
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'rgxp' => 'email')
        ));

        if ($objEvent->maxEscortsPerMember > 0)
        {
            $objForm->addFormField('escorts', array(
                'label' => 'Anzahl Begleitpersonen',
                'inputType' => 'select',
                'options' => range(0, $objEvent->maxEscortsPerMember),
                'eval' => array('mandatory' => true, 'rgxp' => 'natural')
            ));
        }


        // Need a checkbox?
        $objForm->addFormField('termsOfUse', array(
            'label' => array('Nutzungsbedingungen', 'Ich bin mit den Nutzungsbedingungen einverstanden.'),
            'inputType' => 'checkbox',
            'eval' => array('mandatory' => true)
        ));

        // Need a checkbox?
        $objForm->addFormField('captcha', array(
            'label' => array('Nutzungsbedingungen', 'Ich bin mit den Nutzungsbedingungen einverstanden.'),
            'inputType' => 'captcha',
            'eval' => array('mandatory' => true)
        ));


        // Let's add  a submit button
        $objForm->addFormField('submit', array(
            'label' => 'Für Event anmelden',
            'inputType' => 'submit'
        ));


        $objModel = new CalendarEventsMemberModel();
        $objForm->bindModel($objModel);
        $blnHasError = false;
        if ($objForm->validate())
        {

            // Check if user is already registered
            if (Input::post('email') != '')
            {
                $arrOptions = array(
                    'column' => array('tl_calendar_events_member.email=?', 'tl_calendar_events_member.pid=?'),
                    'value' => array(strtolower(Input::post('email')), $objEvent->id)
                );
                $objMember = CalendarEventsMemberModel::findAll($arrOptions);
                if ($objMember !== null)
                {
                    $objWidget = $objForm->getWidget('email');
                    $errorMsg = sprintf('Eine Anmeldung mit der E-Mail-Adresse "%s" ist bereits eingegangen. Der Anmeldevorgang wurde abgebrochen.', strtolower(Input::post('email')));
                    $objWidget->addError($errorMsg);
                    $blnHasError = true;
                }
            }


            if (!$blnHasError)
            {
                $objModel->pid = $objEvent->id;
                $objModel->email = strtolower($objModel->email);
                $objModel->addedOn = time();
                $objModel->tstamp = time();
                $objModel->save();

                $this->sendEmail($objModel, $objEvent);

                // Log new insert
                $level = LogLevel::INFO;
                $logger = System::getContainer()->get('monolog.logger.contao');
                $strText = 'New booking for event with title "' . $objEvent->title . '"';
                $logger->log($level, $strText, array('contao' => new ContaoContext(__METHOD__, $level)));

                $objPageModel = PageModel::findByPk($this->jumpTo);
                if ($objPageModel === null)
                {
                    Controller::reload();
                }
                Controller::redirect($objPageModel->getFrontendUrl());


            }

        }

        return $objForm->generate();
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
