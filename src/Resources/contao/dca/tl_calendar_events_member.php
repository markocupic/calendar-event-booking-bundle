<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

use Markocupic\ExportTable\ExportTable;

/**
 * Table tl_calendar_events_member
 */
$GLOBALS['TL_DCA']['tl_calendar_events_member'] = array
(

    // Config
    'config' => array
    (
        'dataContainer' => 'Table',
        'ptable' => 'tl_calendar_events',
        'enableVersioning' => true,
        'notCopyable' => true,
        'onsubmit_callback' => array
        (//
        ),
        'onload_callback' => array
        (
            //array('tl_calendar_events_member', 'setStatus'),
            array('tl_calendar_events_member', 'onloadCallback'),
        ),
        'ondelete_callback' => array
        (//
        ),
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary',
                'email,pid' => 'index',
            )
        )
    ),

    // List
    'list' => array
    (
        'sorting' => array
        (
            'mode' => 2,
            'fields' => array('lastname'),
            'flag' => 1,
            'panelLayout' => 'filter;sort,search'
        ),
        'label' => array
        (
            'fields' => array('firstname', 'lastname', 'street', 'city'),
            'showColumns' => true,
        ),
        'global_operations' => array
        (
            'all' => array
            (
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ),
            'downloadRegistrationList' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['downloadRegistrationList'],
                'href' => 'act=downloadRegistrationList',
                'class' => 'download_booking_list',
                'icon' => 'bundles/markocupiccalendareventbooking/icons/excel.png',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )

        ),
        'operations' => array
        (
            'edit' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ),

            'delete' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),

            'show' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg'
            )

        )
    ),

    // Palettes
    'palettes' => array
    (
        'default' => '{booking_date_legend},addedOn;{notes_legend}, notes;{personal_legend},firstname,lastname,gender,dateOfBirth;{address_legend:hide},street,postal,city;{contact_legend},phone,email;{escort_legend},escorts',
    ),

    // Subpalettes
    'subpalettes' => array
    (
        //
    ),


    // Fields
    'fields' => array
    (
        'id' => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['pid'],
            'foreignKey' => 'tl_calendar_events.title',
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => array('type' => 'belongsTo', 'load' => 'eager'),
            'eval' => array('readonly' => true),
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'addedOn' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['addedOn'],
            'sorting' => true,
            'inputType' => 'text',
            'eval' => array('rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'),
            'sql' => "varchar(10) NOT NULL default ''"
        ),
/**
        'status' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['status'],
            'filter' => true,
            'inputType' => 'select',
            'reference' => &$GLOBALS['TL_LANG']['tl_calendar_events_member'],
            'options' => array('accepted', 'refused', 'waitlisted'),
            'eval' => array('doNotShow' => true, 'readonly' => false, 'includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['notConfirmed'], 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
 **/

        'notes' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['notes'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => array('tl_class' => 'clr', 'mandatory' => false),
            'sql' => "text NULL",
        ),
        'firstname' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['firstname'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'lastname' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['lastname'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'gender' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['gender'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'select',
            'options' => array('male', 'female'),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
            'sql' => "varchar(32) NOT NULL default ''"
        ),
        'dateOfBirth' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['dateOfBirth'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'),
            'sql' => "varchar(10) NOT NULL default ''"
        ),
        'street' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['street'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 255, 'feEditable' => true, 'feViewable' => true, 'feGroup' => 'address', 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'postal' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['postal'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 32, 'feEditable' => true, 'feViewable' => true, 'feGroup' => 'address', 'tl_class' => 'w50'),
            'sql' => "varchar(32) NOT NULL default ''"
        ),
        'city' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['city'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 255, 'feEditable' => true, 'feViewable' => true, 'feGroup' => 'address', 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'phone' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['phone'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'feEditable' => true, 'feViewable' => true, 'feGroup' => 'contact', 'tl_class' => 'w50'),
            'sql' => "varchar(64) NOT NULL default ''"
        ),
        'email' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['email'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'unique' => false, 'decodeEntities' => true, 'feEditable' => true, 'feViewable' => true, 'feGroup' => 'contact', 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'escorts' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['escorts'],
            'filter' => true,
            'sorting' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 3, 'tl_class' => 'w50'),
            'sql' => "int(3) unsigned NULL"
        ),
    )
);


/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_calendar_events_member extends Backend
{

    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     *
     * OnLoad Callback
     */
    public function onloadCallback()
    {
        // Download the registration list as a csv spreadsheet
        if (Input::get('act') == 'downloadRegistrationList') {
            $opt = array();
            $opt['arrSelectedFields'] = array('pid', 'addedOn', 'firstname', 'lastname', 'gender', 'street', 'postal', 'city', 'phone', 'email');
            $opt['exportType'] = 'csv';
            $opt['arrFilter'][] = array('pid=?', Input::get('id'));
            $opt['strDestinationCharset'] = 'windows-1252';
            $GLOBALS['TL_HOOKS']['exportTable'][] = array('Markocupic\CalendarEventBookingBundle\ExportTableHook', 'exportBookingListHook');
            ExportTable::exportTable('tl_calendar_events_member', $opt);
            exit;
        }

    }


}
