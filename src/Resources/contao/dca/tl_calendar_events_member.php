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

$GLOBALS['TL_DCA']['tl_calendar_events_member'] = array(
	// Config
	'config'   => array(
		'dataContainer'     => 'Table',
		'ptable'            => 'tl_calendar_events',
		'enableVersioning'  => true,
		'notCopyable'       => true,
		'onsubmit_callback' => array(
		),
		'onload_callback'   => array(
			array('Markocupic\CalendarEventBookingBundle\Contao\Dca\TlCalendarEventsMember', 'downloadRegistrationList'),
		),
		'ondelete_callback' => array(
		),
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
				'href'       => 'act=downloadRegistrationList',
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
			'foreignKey' => 'tl_calendar_events.title',
			'sql'        => "int(10) unsigned NOT NULL default '0'",
			'relation'   => array('type' => 'belongsTo', 'load' => 'eager'),
			'eval'       => array('readonly' => true),
		),
		'tstamp'       => array(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'addedOn'      => array(
			'sorting'   => true,
			'inputType' => 'text',
			'eval'      => array('rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'),
			'sql'       => "varchar(10) NOT NULL default ''",
		),
		'notes'        => array(
			'exclude'   => true,
			'inputType' => 'textarea',
			'eval'      => array('tl_class' => 'clr', 'mandatory' => false),
			'sql'       => "text NULL",
		),
		'firstname'    => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'lastname'     => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'gender'       => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'select',
			'options'   => array('male', 'female'),
			'reference' => &$GLOBALS['TL_LANG']['MSC'],
			'eval'      => array('includeBlankOption' => true, 'tl_class' => 'w50'),
			'sql'       => "varchar(32) NOT NULL default ''",
		),
		'dateOfBirth'  => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'),
			'sql'       => "varchar(11) NOT NULL default ''"
		),
		'street'       => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'postal'       => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('maxlength' => 32, 'tl_class' => 'w50'),
			'sql'       => "varchar(32) NOT NULL default ''",
		),
		'city'         => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'phone'        => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'tl_class' => 'w50'),
			'sql'       => "varchar(64) NOT NULL default ''",
		),
		'email'        => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'escorts'      => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('maxlength' => 3, 'tl_class' => 'w50'),
			'sql'       => "int(3) unsigned NULL",
		),
		'bookingToken' => array(
			'filter'    => true,
			'sorting'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
	),
);
