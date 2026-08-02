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

$GLOBALS['TL_DCA']['tl_calendar_events_payment'] = [
    'config'   => [
        'dataContainer'    => DC_Table::class,
        'ptable'           => 'tl_calendar_events_order',
        'enableVersioning' => true,
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
            'fields'      => ['type ASC'],
            'flag'        => DataContainer::SORT_DAY_DESC,
            'panelLayout' => 'filter;sort,search',
        ],
        'label'             => [
            'fields'      => ['type', 'status', 'captureTime', 'grossAmount', 'currencyCode', 'refundTime', 'refundAmount'],
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
            'all',
        ],
    ],
    'palettes' => [
        'default' => '{payment_legend},bookingUuid,orderUuid,type,provider,providerOrderId,providerCaptureId,captureTime,status,isFinal,grossAmount,netAmountReceived,captureFee,currencyCode;{refund_legend},refundTime,refundAmount,refundFee;{details_legend},details;{notes_legend},notes',
    ],
    'fields'   => [
        'id'                => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'               => [
            'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
            'foreignKey' => 'tl_calendar_events_member.id',
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp'            => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'uuid'              => [
            'default'   => Uuid::uuid4()->toString(),
            'eval'      => ['doNotCopy' => true, 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'bookingUuid'       => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'orderUuid'         => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'type'              => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => ['capture', 'refund'],
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default 'capture'",
        ],
        'provider'          => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'providerOrderId'   => [
            'eval'      => ['doNotCopy' => true, 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'providerCaptureId' => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'captureTime'       => [
            'eval'      => ['doNotCopy' => true, 'datepicker' => true, 'rgxp' => 'datim', 'tl_class' => 'w50 wizard'],
            'flag'      => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 10, 'notnull' => true, 'default' => ''],
        ],
        'status'            => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'isFinal'           => [
            'eval'      => ['tl_class' => 'clr cbx m12'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'grossAmount'       => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        'netAmountReceived' => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        'captureFee'        => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        'currencyCode'      => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['CHF', 'EUR', 'GBP', 'USD'],
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default 'EUR'",
        ],
        'providerRefundId'  => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'refundTime'        => [
            'eval'      => ['doNotCopy' => true, 'datepicker' => true, 'rgxp' => 'datim', 'tl_class' => 'w50 wizard'],
            'flag'      => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 10, 'notnull' => true, 'default' => ''],
        ],
        'refundAmount'      => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        'refundFee'         => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        'details'           => [
            'eval'      => ['doNotCopy' => true, 'mandatory' => false, 'readonly' => true, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => "mediumtext NOT NULL default ''",
        ],
        'notes'             => [
            'eval'      => ['mandatory' => false, 'tl_class' => 'w50', 'useRawRequestData' => true],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => "mediumtext NOT NULL default ''",
        ],
    ],
];
