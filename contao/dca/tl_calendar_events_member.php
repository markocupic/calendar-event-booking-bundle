<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Ramsey\Uuid\Uuid;
use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Platforms\MySQLPlatform;

$GLOBALS['TL_DCA']['tl_calendar_events_member'] = [
    'config'   => [
        'dataContainer'     => DC_Table::class,
        'ptable'            => 'tl_calendar_events',
        'enableVersioning'  => true,
        'onsubmit_callback' => [],
        'ondelete_callback' => [],
        'doNotCopyRecords'  => true,
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
            'mode'        => DataContainer::MODE_SORTABLE,
            'fields'      => ['lastname'],
            'flag'        => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search',
        ],
        'label'             => [
            'fields'      => ['firstname', 'lastname', 'street', 'city'],
            'showColumns' => true,
        ],
        'global_operations' => [
            'all'                        => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
            'downloadEventRegistrations' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['downloadEventRegistrations'],
                'href'       => 'action=downloadEventRegistrations',
                'class'      => 'download_registration_list',
                'icon'       => 'bundles/markocupiccalendareventbooking/icons/excel.png',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.svg',
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.svg',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '
        {booking_date_legend},addedOn;
        {notes_legend},notes;
        {personal_legend},firstname,lastname,gender,dateOfBirth;
        {address_legend:hide},street,postal,city;
        {contact_legend},phone,email;
        {escort_legend},escorts
        ',
    ],
    'fields'   => [
        'id'           => [
            'sql' => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'autoincrement' => true],
        ],
        'pid'          => [
            'eval'       => ['readonly' => true],
            'foreignKey' => 'tl_calendar_events.title',
            'relation'   => ['type' => 'belongsTo', 'load' => 'eager'],
            'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
        ],
        'tstamp'       => [
            'sql' => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
        ],
        'addedOn'      => [
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 10, 'notnull' => true, 'default' => ''],
        ],
        'notes'        => [
            'default'   => null,
            'eval'      => ['tl_class' => 'clr', 'mandatory' => false],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => 'mediumtext NULL',
        ],
        'firstname'    => [
            'eval'      => ['mandatory' => true, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'lastname'     => [
            'eval'      => ['mandatory' => true, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'gender'       => [
            'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['male', 'female', 'other'],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 32, 'notnull' => true, 'default' => ''],
        ],
        'dateOfBirth'  => [
            'eval'      => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 11, 'notnull' => true, 'default' => ''],
        ],
        'street'       => [
            'eval'      => ['maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'postal'       => [
            'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 32, 'notnull' => true, 'default' => ''],
        ],
        'city'         => [
            'eval'      => ['maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'phone'        => [
            'eval'      => ['maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 64, 'notnull' => true, 'default' => ''],
        ],
        'email'        => [
            'eval'      => ['mandatory' => true, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'escorts'      => [
            'default'   => null,
            'eval'      => ['maxlength' => 3, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'integer', 'length' => 3, 'unsigned' => true, 'notnull' => false],
        ],
        'bookingToken' => [
            'eval'      => ['doNotCopy' => true, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'default'   => Uuid::uuid4()->toString(),
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
    ],
];
