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
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook\DecimalPriceRegexpListener;
use Ramsey\Uuid\Uuid;

$GLOBALS['TL_DCA']['tl_calendar_events_order'] = [
    'config'   => [
        'dataContainer'    => DC_Table::class,
        'ptable'           => 'tl_calendar_events_member',
        'ctable'           => ['tl_calendar_events_payment'],
        'enableVersioning' => true,
        'sql'              => [
            'keys' => [
                'id'                       => 'primary',
                'pid'                      => 'index',
                // One order per checkout/order id of a payment provider. Belt and
                // braces next to the row locking in the payment handlers: it makes
                // a double booking impossible even under a race condition, e.g.
                // when the return page and the provider webhook process the same
                // transaction at the very same moment.
                'provider,providerOrderId' => 'unique',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => DataContainer::MODE_SORTABLE,
            'fields'      => ['createTime ASC'],
            'flag'        => DataContainer::SORT_DAY_DESC,
            'panelLayout' => 'filter;sort,search',
        ],
        'label'             => [
            'fields'      => ['createTime', 'payerEmail', 'grossAmount', 'currencyCode', 'status', 'provider', 'providerOrderId'],
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
            'edit'    => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'payment' => [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_order']['payment'],
                'href'  => 'do=calendar&table=tl_calendar_events_payment',
                'icon'  => 'bundles/markocupiccalendareventbooking/icons/circle-dollar-sign.svg',
            ],
            'delete'  => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false" data-action="contao--scroll-offset#store"',
            ],
            'show'    => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{order_legend},uuid,bookingUuid,provider,providerOrderId,createTime,status,payerId,payerEmail,grossAmount,netAmount,vatAmount,currencyCode,details,notes',
    ],
    'fields'   => [
        'id'              => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'             => [
            'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
            'foreignKey' => 'tl_calendar_events_member.id',
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp'          => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'uuid'            => [
            'default'   => Uuid::uuid4()->toString(),
            'eval'      => ['doNotCopy' => true, 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'bookingUuid'     => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'provider'        => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'providerOrderId' => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'createTime'      => [
            'eval'      => ['doNotCopy' => true, 'datepicker' => true, 'rgxp' => 'datim', 'tl_class' => 'w50 wizard'],
            'flag'      => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 10, 'notnull' => true, 'default' => ''],
        ],
        'status'          => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'payerId'         => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'payerEmail'      => [
            'eval'      => ['mandatory' => false, 'rgxp' => 'email', 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'grossAmount'     => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        'netAmount'       => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        'vatAmount'       => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        'currencyCode'    => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['CHF', 'EUR', 'GBP', 'USD'],
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default 'EUR'",
        ],
        'details'         => [
            'eval'      => ['doNotCopy' => true, 'mandatory' => false, 'readonly' => true, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => "mediumtext NOT NULL default ''",
        ],
        'notes'           => [
            'eval'      => ['mandatory' => false, 'tl_class' => 'w50', 'useRawRequestData' => true],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => "mediumtext NOT NULL default ''",
        ],
    ],
];
