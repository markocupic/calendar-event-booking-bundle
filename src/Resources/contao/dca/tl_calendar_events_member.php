<?php

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Markocupic\CalendarEventBookingBundle\Contao\Dca\TlCalendarEventsMember;

$GLOBALS['TL_DCA']['tl_calendar_events_member'] = array(
	// Config
	'config'   => array(
		'dataContainer'     => 'Table',
		'ptable'            => 'tl_calendar_events',
		'enableVersioning'  => true,
		'notCopyable'       => true,
		'onsubmit_callback' => array(),
		'onload_callback'   => array(
			array(TlCalendarEventsMember::class, 'downloadRegistrationList'),
		),
		'ondelete_callback' => array(),
		'sql'               => array(
			'keys' => array(
				'id'        => 'primary',
				'email,pid' => 'index',
			),
		),
	),

	// List
	'list'     => array(
		'sorting'           => array(
			'mode'        => 2,
			'fields'      => array('lastname'),
			'flag'        => 1,
			'panelLayout' => 'filter;sort,search',
		),
		'label'             => array(
			'fields'      => array('firstname', 'lastname', 'street', 'city'),
			'showColumns' => true,
		),
		'global_operations' => array(
			'all'                      => array(
				'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'       => 'act=select',
				'class'      => 'header_edit_all',
				'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
			),
			'downloadRegistrationList' => array(
				'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['downloadRegistrationList'],
				'href'       => 'action=downloadRegistrationList',
				'class'      => 'download_booking_list',
				'icon'       => 'bundles/markocupiccalendareventbooking/icons/excel.png',
				'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
			),
		),
		'operations'        => array(
			'edit' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['edit'],
				'href'  => 'act=edit',
				'icon'  => 'edit.svg',
			),

			'delete' => array(
				'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['delete'],
				'href'       => 'act=delete',
				'icon'       => 'delete.svg',
				'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
			),

			'show' => array(
				'label' => &$GLOBALS['TL_LANG']['tl_calendar_events_member']['show'],
				'href'  => 'act=show',
				'icon'  => 'show.svg',
			),
		),
	),

	// Palettes
	'palettes' => array(
		'default' => '{booking_date_legend},addedOn;{notes_legend},notes;{personal_legend},firstname,lastname,gender,dateOfBirth;{address_legend:hide},street,postal,city;{contact_legend},phone,email;{escort_legend},escorts',
	),

	// Fields
	'fields'   => array(
		'id'           => array(
			'sql' => "int(10) unsigned NOT NULL auto_increment",
		),
		'pid'          => array(
			'eval'       => array('readonly' => true),
			'foreignKey' => 'tl_calendar_events.title',
			'relation'   => array('type' => 'belongsTo', 'load' => 'eager'),
			'sql'        => "int(10) unsigned NOT NULL default '0'",
		),
		'tstamp'       => array(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'addedOn'      => array(
			'eval'      => array('rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'),
			'inputType' => 'text',
			'sorting'   => true,
			'sql'       => "varchar(10) NOT NULL default ''",
		),
		'notes'        => array(
			'default'   => null,
			'eval'      => array('tl_class' => 'clr', 'mandatory' => false),
			'exclude'   => true,
			'inputType' => 'textarea',
			'search'    => true,
			'sql'       => "text NULL",
		),
		'firstname'    => array(
			'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'lastname'     => array(
			'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'gender'       => array(
			'eval'      => array('includeBlankOption' => true, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'select',
			'options'   => array('male', 'female', 'other'),
			'reference' => &$GLOBALS['TL_LANG']['MSC'],
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(32) NOT NULL default ''",
		),
		'dateOfBirth'  => array(
			'eval'      => array('rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(11) NOT NULL default ''"
		),
		'street'       => array(
			'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'postal'       => array(
			'eval'      => array('maxlength' => 32, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(32) NOT NULL default ''",
		),
		'city'         => array(
			'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'phone'        => array(
			'eval'      => array('maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(64) NOT NULL default ''",
		),
		'email'        => array(
			'eval'      => array('mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'escorts'      => array(
			'default'   => null,
			'eval'      => array('maxlength' => 3, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "int(3) unsigned NULL",
		),
		'bookingToken' => array(
			'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
			'filter'    => true,
			'inputType' => 'text',
			'search'    => true,
			'sorting'   => true,
			'sql'       => "varchar(255) NOT NULL default ''",
		),
	),
);
