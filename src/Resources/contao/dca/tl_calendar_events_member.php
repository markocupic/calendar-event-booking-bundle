<?php

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

$GLOBALS['TL_DCA']['tl_calendar_events_member'] = [

    // Config
    'config'   => [
        'dataContainer'     => 'Table',
        'ptable'            => 'tl_calendar_events',
        'enableVersioning'  => true,
        'notCopyable'       => true,
        'onsubmit_callback' => [//
        ],
        'onload_callback'   => [
            ['tl_calendar_events_member', 'onloadCallback'],
        ],
        'ondelete_callback' => [//
        ],
        'sql'               => [
            'keys' => [
                'id'        => 'primary',
                'email,pid' => 'index',
            ],
        ],
    ],

    // List
    'list'     => [
        'sorting'           => [
            'mode'        => 2,
            'fields'      => ['lastname'],
            'flag'        => 1,
            'panelLayout' => 'filter;sort,search',
        ],
        'label'             => [
            'fields'      => ['firstname', 'lastname', 'street', 'city'],
            'showColumns' => true,
        ],
        'global_operations' => [
            'all'                      => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
            'downloadRegistrationList' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['downloadRegistrationList'],
                'href'       => 'act=downloadRegistrationList',
                'class'      => 'download_booking_list',
                'icon'       => 'bundles/markocupiccalendareventbooking/icons/excel.png',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],

        ],
        'operations'        => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.svg',
            ],

            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
            ],

            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.svg',
            ],

        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '{booking_date_legend},addedOn;{notes_legend}, notes;{personal_legend},firstname,lastname,gender,dateOfBirth;{address_legend:hide},street,postal,city;{contact_legend},phone,email;{escort_legend},escorts',
    ],

    // Fields
    'fields'   => [
        'id'           => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'pid'          => [
            'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['pid'],
            'foreignKey' => 'tl_calendar_events.title',
            'sql'        => "int(10) unsigned NOT NULL default '0'",
            'relation'   => ['type' => 'belongsTo', 'load' => 'eager'],
            'eval'       => ['readonly' => true],
        ],
        'tstamp'       => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'addedOn'      => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['addedOn'],
            'sorting'   => true,
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'notes'        => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['notes'],
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => ['tl_class' => 'clr', 'mandatory' => false],
            'sql'       => "text NULL",
        ],
        'firstname'    => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['firstname'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'lastname'     => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['lastname'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'gender'       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['gender'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'select',
            'options'   => ['male', 'female'],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'dateOfBirth'  => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['dateOfBirth'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => "varchar(11) NOT NULL default ''"
        ],
        'street'       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['street'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'feEditable' => true, 'feViewable' => true, 'feGroup' => 'address', 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'postal'       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['postal'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 32, 'feEditable' => true, 'feViewable' => true, 'feGroup' => 'address', 'tl_class' => 'w50'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'city'         => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['city'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'feEditable' => true, 'feViewable' => true, 'feGroup' => 'address', 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'phone'        => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['phone'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'email'        => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['email'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'unique' => false, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'escorts'      => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['escorts'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 3, 'tl_class' => 'w50'],
            'sql'       => "int(3) unsigned NULL",
        ],
        'bookingToken' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['bookingToken'],
            'filter'    => true,
            'sorting'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
    ],
];

