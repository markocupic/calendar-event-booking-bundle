<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Ramsey\Uuid\Uuid;

$GLOBALS['TL_DCA']['tl_calendar_events_member'] = [
    'config'   => [
        'dataContainer'    => DC_Table::class,
        'ptable'           => 'tl_calendar_events',
        'ctable'           => ['tl_calendar_events_payment'],
        'enableVersioning' => true,
        'doNotCopyRecords' => true,
        'sql'              => [
            'keys' => [
                'id'        => 'primary',
                'email,pid' => 'index',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => DataContainer::MODE_SORTABLE,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields'      => ['addedOn', 'ticketAmount', 'gender', 'firstname', 'lastname', 'street', 'city', 'temporaryReserved', 'expired', 'canceled', 'optIn', 'waitingList', 'paid'],
            'showColumns' => true,
        ],
        'global_operations' => [
            'all'                   => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'data-action="contao--scroll-offset#store" accesskey="e"',
            ],
            'downloadEventBookings' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['downloadEventBookings'],
                'href'       => 'action=downloadEventBookings',
                'class'      => 'download_event_bookings',
                'icon'       => 'bundles/markocupiccalendareventbooking/icons/file-down.svg',
                'attributes' => 'data-action="contao--scroll-offset#store" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'         => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.svg',
            ],
            'copy'         => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.svg',
            ],
            'delete'       => [
                'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false" data-action="contao--scroll-offset#store"',
            ],
            'show'         => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.svg',
            ],
            'payment'      => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['payment'],
                'href'  => 'do=calendar&table=tl_calendar_events_payment',
                'icon'  => 'bundles/markocupiccalendareventbooking/icons/circle-dollar-sign.svg',
            ],
            'notification' => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['notification'],
                'href'  => 'do=calendar&table=tl_calendar_events_booking_notification',
                'icon'  => 'bundles/markocupiccalendareventbooking/icons/mail.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '
        {booking_date_legend},addedOn;
        {booking_legend},temporaryReserved,optIn,canceled,expired,waitingList,paid,ticketAmount,escorts,checkoutHandler;
        {notes_legend},notes;
        {personal_legend},firstname,lastname,gender,dateOfBirth;
        {address_legend:hide},street,postal,city;
        {contact_legend},phone,email;
        {form_legend:hide},form,formSubmit;
        ',
    ],
    'fields'   => [
        'id'                => [
            'sql' => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'autoincrement' => true],
        ],
        'pid'               => [
            'eval'       => ['readonly' => true],
            'foreignKey' => 'tl_calendar_events.title',
            'relation'   => ['type' => 'belongsTo', 'load' => 'eager'],
            'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
        ],
        'tstamp'            => [
            'sql' => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
        ],
        'addedOn'           => [
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'flag'      => DataContainer::SORT_DAY_BOTH,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 10, 'notnull' => true, 'default' => ''],
        ],
        'member'            => [
            'foreignKey' => 'tl_member.CONCAT(firstname," ",lastname)',
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
            'sql'        => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'waitingList'       => [
            'eval'      => ['tl_class' => 'w50', 'disabled' => true],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'sorting'   => true,
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'optIn'             => [
            'eval'      => ['tl_class' => 'w50', 'disabled' => true],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'sorting'   => true,
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'canceled'          => [
            'eval'      => ['tl_class' => 'w50', 'disabled' => true],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'sorting'   => true,
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'temporaryReserved' => [
            'eval'      => ['tl_class' => 'w50', 'disabled' => true],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'sorting'   => true,
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'expired'           => [
            'eval'      => ['tl_class' => 'w50', 'disabled' => true],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'sorting'   => true,
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'paid'              => [
            'eval'      => ['disabled' => true, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'sorting'   => true,
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'firstname'         => [
            'eval'      => ['mandatory' => true, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'lastname'          => [
            'eval'      => ['mandatory' => true, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'gender'            => [
            'eval'      => ['includeBlankOption' => true, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['male', 'female', 'other'],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 32, 'notnull' => true, 'default' => ''],
        ],
        'dateOfBirth'       => [
            'eval'      => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 11, 'notnull' => true, 'default' => ''],
        ],
        'street'            => [
            'eval'      => ['maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'postal'            => [
            'eval'      => ['maxlength' => 32, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 32, 'notnull' => true, 'default' => ''],
        ],
        'city'              => [
            'eval'      => ['maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'phone'             => [
            'eval'      => ['maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 64, 'notnull' => true, 'default' => ''],
        ],
        'email'             => [
            'eval'      => ['mandatory' => true, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'ticketAmount'      => [
            'default'   => 1,
            'eval'      => ['maxlength' => 3, 'rgxp' => 'natural', 'tl_class' => 'clr w50'],
            'filter'    => true,
            'inputType' => 'select',
            'options'   => range(1, 100),
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'integer', 'length' => 3, 'unsigned' => true, 'notnull' => true, 'default' => 1],
        ],
        'escorts'           => [
            'default'   => 0,
            'eval'      => ['maxlength' => 3, 'rgxp' => 'natural', 'tl_class' => 'w50'],
            'filter'    => true,
            'inputType' => 'select',
            'options'   => range(0, 100),
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'integer', 'length' => 3, 'unsigned' => true, 'notnull' => true, 'default' => 0],
        ],
        'bookingToken'      => [
            'eval'      => ['doNotCopy' => true, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'default'   => Uuid::uuid4()->toString(),
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'form'              => [
            'eval'       => ['readonly' => true, 'tl_class' => 'w50'],
            'foreignKey' => 'tl_form.title',
            'inputType'  => 'select',
            'relation'   => ['type' => 'belongsTo', 'load' => 'eager'],
            'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
        ],
        'formSubmit'        => [
            'eval'      => ['mandatory' => false, 'readonly' => true, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => "mediumtext NOT NULL default ''",
        ],
        'notes'             => [
            'default'   => null,
            'eval'      => ['tl_class' => 'w50', 'mandatory' => false],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT, 'notnull' => false],
        ],
        'checkoutHandler'   => [
            'eval'      => ['readonly' => true, 'doNotCopy' => true, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'default'   => Uuid::uuid4()->toString(),
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => 'default'],
        ],
    ],
];
