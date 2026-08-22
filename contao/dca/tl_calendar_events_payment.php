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
        'default' => '{payment_legend},bookingUuid,orderUuid,type,provider,providerOrderId,providerCaptureId,captureTime,status,isFinal,grossAmount,currencyCode;{settlement_legend},exchangeRate,settlementGrossAmount,settlementCurrencyCode,captureFee,netAmountReceived;{refund_legend},providerRefundId,refundTime,refundAmount,refundExchangeRate,refundSettlementAmount,refundFee;{details_legend},details;{notes_legend},notes',
    ],
    'fields'   => [
        'id'                     => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'                    => [
            'sql'        => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'notnull' => true, 'default' => 0],
            'foreignKey' => 'tl_calendar_events_member.id',
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp'                 => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'uuid'                   => [
            'default'   => Uuid::uuid4()->toString(),
            'eval'      => ['doNotCopy' => true, 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'bookingUuid'            => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'orderUuid'              => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'type'                   => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => ['capture', 'refund'],
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default 'capture'",
        ],
        'provider'               => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'providerOrderId'        => [
            'eval'      => ['doNotCopy' => true, 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'providerCaptureId'      => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'captureTime'            => [
            'eval'      => ['doNotCopy' => true, 'datepicker' => true, 'rgxp' => 'datim', 'tl_class' => 'w50 wizard'],
            'flag'      => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 10, 'notnull' => true, 'default' => ''],
        ],
        'status'                 => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'isFinal'                => [
            'eval'      => ['tl_class' => 'clr cbx m12'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        // What the customer paid, in currencyCode.
        'grossAmount'            => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        // settlementGrossAmount minus captureFee, in settlementCurrencyCode.
        'netAmountReceived'      => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        // The provider fee, in settlementCurrencyCode.
        'captureFee'             => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        // The currency the customer was charged in. Applies to grossAmount.
        'currencyCode'           => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['CHF', 'EUR', 'GBP', 'USD'],
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default 'EUR'",
        ],
        // How many units of settlementCurrencyCode one unit of currencyCode bought.
        //
        // 1 when the two currencies are the same, which is the ordinary case. When
        // they are not, this is the only thing that connects grossAmount to
        // settlementGrossAmount - without it the row holds two amounts that cannot
        // be reconciled with each other, and no amount of arithmetic recovers the
        // rate the provider actually applied.
        //
        // Kept as the provider reported it, at full precision. Rounding a rate to
        // two places would put the reconstruction off by cents on a three digit
        // payment.
        'exchangeRate'           => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        // What the customer paid, converted into settlementCurrencyCode - the sum
        // the fee is taken from.
        //
        // This is the figure that makes the row add up: settlementGrossAmount minus
        // captureFee equals netAmountReceived, all three in the same currency.
        // grossAmount cannot play that part when the currencies differ, and a
        // record whose own numbers do not agree is one nobody trusts, whichever of
        // them happens to be right.
        'settlementGrossAmount'  => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        // The currency the provider settled in - the currency of the account the
        // money is credited to. Applies to netAmountReceived and captureFee.
        //
        // Usually the same as currencyCode, but only usually. Stripe reports the fee
        // and the net amount on the balance transaction, and that is denominated in
        // the account currency rather than in the one the customer saw. Without this
        // column a payment charged in EUR and settled in CHF puts two currencies in
        // one row under a single label, and nothing says so.
        //
        // An empty value means the provider did not report it - not that it equals
        // currencyCode. Treating those two as the same is the assumption this column
        // exists to remove.
        'settlementCurrencyCode' => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['CHF', 'EUR', 'GBP', 'USD'],
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        // The provider's id for the refund, the counterpart of providerCaptureId.
        'providerRefundId'       => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'refundTime'             => [
            'eval'      => ['doNotCopy' => true, 'datepicker' => true, 'rgxp' => 'datim', 'tl_class' => 'w50 wizard'],
            'flag'      => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => ['type' => 'string', 'length' => 10, 'notnull' => true, 'default' => ''],
        ],
        // What the customer got back, in currencyCode - the same currency the
        // payment was charged in, because no provider refunds in another one.
        'refundAmount'           => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        // The rate of the refund, which is not the rate of the payment.
        //
        // A refund gets its own balance transaction at the provider, converted at
        // the rate of the day it was issued. Refund a payment months later and the
        // account is debited by a different amount than it was credited with -
        // that difference is a real exchange loss, and without this column there is
        // nothing in the record to explain where it came from.
        //
        // 1 when nothing was converted, 0 when no rate is known - see exchangeRate.
        'refundExchangeRate'     => [
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        // refundAmount converted into settlementCurrencyCode - what the refund
        // actually took off the account, before the refund fee.
        'refundSettlementAmount' => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        // The provider fee for the refund, in settlementCurrencyCode.
        'refundFee'              => [
            'eval'      => ['mandatory' => false, 'maxlength' => MySQLPlatform::LENGTH_LIMIT_TINYTEXT, 'rgxp' => DecimalPriceRegexpListener::REGEXP_NAME, 'tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'DOUBLE PRECISION DEFAULT 0 NOT NULL default 0',
        ],
        'details'                => [
            'eval'      => ['doNotCopy' => true, 'mandatory' => false, 'readonly' => true, 'tl_class' => 'clr w50'],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => "mediumtext NOT NULL default ''",
        ],
        'notes'                  => [
            'eval'      => ['mandatory' => false, 'tl_class' => 'w50', 'useRawRequestData' => true],
            'exclude'   => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => "mediumtext NOT NULL default ''",
        ],
    ],
];
