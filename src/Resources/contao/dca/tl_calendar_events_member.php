<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Markocupic\CalendarEventBookingBundle\Contao\Dca\TlCalendarEventsMember;

$GLOBALS['TL_DCA']['tl_calendar_events_member'] = [
    // Config
    'config'   => [
        'dataContainer'     => 'Table',
        'ptable'            => 'tl_calendar_events',
        'enableVersioning'  => true,
        'notCopyable'       => true,
        'onsubmit_callback' => [],
        'onload_callback'   => [
            [
                TlCalendarEventsMember::class,
                'downloadRegistrationList',
            ],
        ],
        'ondelete_callback' => [],
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
            'fields'      => [
                'firstname',
                'lastname',
                'street',
                'city',
            ],
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
                'href'       => 'action=downloadRegistrationList',
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
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
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
        'default' => '{booking_date_legend},addedOn;{notes_legend},notes;{personal_legend},firstname,lastname,gender,dateOfBirth;{address_legend:hide},street,postal,city;{contact_legend},phone,email;{escort_legend},escorts',
    ],

    // Fields
    'fields'   => [
        'id'           => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'          => [
            'eval'       => ['readonly' => true],
            'foreignKey' => 'tl_calendar_events.title',
            'relation'   => [
                'type' => 'belongsTo',
                'load' => 'eager',
            ],
            'sql'        => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp'       => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'addedOn'      => [
            'eval'      => [
                'rgxp'       => 'datim',
                'datepicker' => true,
                'tl_class'   => 'w50 wizard',
            ],
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'notes'        => [
            'default'   => null,
            'eval'      => [
                'tl_class'  => 'clr',
                'mandatory' => false,
            ],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => 'text NULL',
        ],
        'firstname'    => [
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'lastname'     => [
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'gender'       => [
            'eval'      => [
                'includeBlankOption' => true,
                'tl_class'           => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'select',
            'options'   => [
                'male',
                'female',
                'other',
            ],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'dateOfBirth'  => [
            'eval'      => [
                'rgxp'       => 'date',
                'datepicker' => true,
                'tl_class'   => 'w50 wizard',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(11) NOT NULL default ''",
        ],
        'street'       => [
            'eval'      => [
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'postal'       => [
            'eval'      => [
                'maxlength' => 32,
                'tl_class'  => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'city'         => [
            'eval'      => [
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'phone'        => [
            'eval'      => [
                'maxlength'      => 64,
                'rgxp'           => 'phone',
                'decodeEntities' => true,
                'tl_class'       => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'email'        => [
            'eval'      => [
                'mandatory'      => true,
                'maxlength'      => 255,
                'rgxp'           => 'email',
                'decodeEntities' => true,
                'tl_class'       => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'escorts'      => [
            'default'   => null,
            'eval'      => [
                'maxlength' => 3,
                'tl_class'  => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'int(3) unsigned NULL',
        ],
        'bookingToken' => [
            'eval'      => [
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
    ],
];
