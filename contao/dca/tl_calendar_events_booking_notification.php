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

$GLOBALS['TL_DCA']['tl_calendar_events_booking_notification'] = [
    'config'   => [
        'dataContainer'    => DC_Table::class,
        'ptable'           => 'tl_calendar_events_member',
        'enableVersioning' => true,
        'closed'           => true,
        'sql'              => [
            'keys' => [
                'id'  => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => DataContainer::MODE_SORTABLE,
            'fields'      => ['deliveredOn ASC'],
            'flag'        => DataContainer::SORT_DAY_DESC,
            'panelLayout' => 'filter;sort,search',
        ],
        'label'             => [
            'fields'      => ['', 'delivered', 'deliveredOn', 'type', 'recipientsTo', 'recipientsCc', 'recipientsBcc', 'subject'],
            'showColumns' => true,
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'data-action="contao--scroll-offset#store" accesskey="e"',
            ],
        ],
        'operations'        => [
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{default_legend},deliveredOn,type,delivered,senderAddress,senderName,replyTo,recipientsTo,recipientsCc,recipientsBcc,subject,text,html,attachments,embeddedImages,exception',
    ],
    'fields'   => [
        'id'             => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'            => [
            'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
            'foreignKey' => 'tl_calendar_events_member.id',
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp'         => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'deliveredOn'    => [
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'flag'      => DataContainer::SORT_DAY_BOTH,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'type'           => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'delivered'      => [
            'eval'      => ['mandatory' => false, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'sorting'   => true,
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'senderAddress'  => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'senderName'     => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'replyTo'        => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'recipientsTo'   => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'recipientsCc'   => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'recipientsBcc'  => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'subject'        => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'notnull' => true, 'default' => ''],
        ],
        'text'           => [
            'eval'        => ['mandatory' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'inputType'   => 'textarea',
            'search'      => true,
            'sql'         => 'mediumtext NULL',
        ],
        'html'           => [
            'eval'        => ['mandatory' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'inputType'   => 'textarea',
            'search'      => true,
            'sql'         => 'mediumtext NULL',
        ],
        'attachments'    => [
            'eval'        => ['mandatory' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'inputType'   => 'textarea',
            'search'      => true,
            'sql'         => 'mediumtext NULL',
        ],
        'embeddedImages' => [
            'eval'        => ['mandatory' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'inputType'   => 'textarea',
            'search'      => true,
            'sql'         => 'mediumtext NULL',
        ],
        'exception'      => [
            'eval'      => ['mandatory' => false, 'maxlength' => 1024, 'tl_class' => 'clr'],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 1024, 'notnull' => true, 'default' => ''],
        ],
    ],
];
