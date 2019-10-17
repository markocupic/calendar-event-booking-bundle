<?php

/**
 * @copyright  Marko Cupic 2019
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */

namespace Markocupic\CalendarEventBookingBundle;

use Contao\Controller;
use Contao\Database;
use Contao\System;
use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;

/**
 * Class InitializeSystemHook
 * @package Markocupic\CalendarEventBookingBundle
 */
class InitializeSystemHook
{

    /**
     * Register hook && enable hook overriding in a custom module
     * $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'] is set in config.php
     */
    public function registerHooks()
    {
        if (TL_MODE === 'FE')
        {
            // Register hook && enable hook overriding in a custom module
            // $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'] is set in config.php
            if (!empty($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']) && is_array($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS']))
            {
                foreach ($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'] as $key => $arrHook)
                {
                    if (!empty($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'][$key]) && is_array($GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'][$key]))
                    {
                        if (count($arrHook) === 2)
                        {
                            $GLOBALS['TL_HOOKS'][$key][] = $GLOBALS['CALENDAR_EVENT_BOOKING_BUNDLE']['HOOKS'][$key];
                        }
                    }
                }
            }
        }
    }

    /**
     * Keep running older settings
     */
    public function onUpdate()
    {
        if (TL_MODE === 'BE')
        {
            if (Database::getInstance()->fieldExists('isCalendarEventBookingForm', 'tl_form'))
            {
                // In versions < 3.2 the form hooks where triggered if tl_form.formID was 'event-booking-form'
                Database::getInstance()->prepare("UPDATE tl_form SET isCalendarEventBookingForm='1' WHERE formID=? AND isCalendarEventBookingForm=?")->execute('event-booking-form', '');
            }
        }
    }

    /**
     * auto generate event booking form, if it doesn't exists
     */
    public function autoGenerateBookingForm()
    {
        if (Database::getInstance()->fieldExists('formID', 'tl_form'))
        {
            $objForm = Database::getInstance()->prepare('SELECT * FROM tl_form WHERE formID=?')->execute('event-booking-form');
            if ($objForm->numRows)
            {
                // Return if form already exists
                return;
            }
        }

        if (Database::getInstance()->fieldExists('isCalendarEventBookingForm', 'tl_form'))
        {
            $objForm = Database::getInstance()->prepare('SELECT * FROM tl_form WHERE isCalendarEventBookingForm=?')->execute('1');
            if ($objForm->numRows)
            {
                // Return if form already exists
                return;
            }
        }

        $sqlTlForm = file_get_contents(TL_ROOT . '/vendor/markocupic/calendar-event-booking-bundle/src/Resources/sql/tl_form.sql');
        $sqlTlFormField = file_get_contents(TL_ROOT . '/vendor/markocupic/calendar-event-booking-bundle/src/Resources/sql/tl_form_field.sql');

        // Set tstamp
        $sqlTlForm = str_replace('##tstamp##', time(), $sqlTlForm);

        // Insert into tl_form
        $objInsertStmt1 = Database::getInstance()->query($sqlTlForm);
        if (($intInsertId = $objInsertStmt1->insertId) > 0)
        {
            // Load dca tl_form_field
            Controller::loadDataContainer('tl_form_field');
            $strExtensions = $GLOBALS['TL_DCA']['tl_form_field']['fields']['extensions']['default'];

            // Set pid & tstamp
            $sqlTlFormField = str_replace('##pid##', $intInsertId, $sqlTlFormField);
            $sqlTlFormField = str_replace('##tstamp##', time(), $sqlTlFormField);
            $sqlTlFormField = str_replace('##extensions##', $strExtensions, $sqlTlFormField);

            // Insert into tl_form_field
            $objInsertStmt2 = Database::getInstance()->query($sqlTlFormField);

            // Log form insert
            if ($objInsertStmt2->insertId > 0)
            {
                // Import logger object
                $logger = System::getContainer()->get('monolog.logger.contao');
                $strLog = 'Auto generated calendar event booking form.';
                $logger->log(LogLevel::INFO, $strLog, array('contao' => new ContaoContext(__METHOD__, 'INFO')));
            }
        }
    }
}
