<?php

/**
 * @copyright  Marko Cupic 2019
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */


/**
 * Table tl_calendar_events
 */

// Table config
$GLOBALS['TL_DCA']['tl_calendar']['config']['ctable'][] = 'tl_calendar_events_member';


// Overwrite child record callback callback
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['child_record_callback'] = array('tl_calendar_event_booking', 'listEvents');


// Palettes
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('booking_options_legend', 'details_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addLegend('notification_center_legend', 'booking_options_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addLegend('event_unsubscribe_legend', 'notification_center_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(array('addBookingForm'), 'booking_options_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->addField(array('enableNotificationCenter'), 'notification_center_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->addField(array('enableDeregistration'), 'event_unsubscribe_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->addField(array('street', 'postal', 'city'), 'location', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_calendar_events');


// Selector
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'addBookingForm';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'enableNotificationCenter';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'enableDeregistration';


// Subpalettes
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['addBookingForm'] = 'maxMembers,maxEscortsPerMember,bookingStartDate,bookingEndDate';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['enableNotificationCenter'] = 'eventBookingNotificationCenterIds,eventBookingNotificationSender';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['enableDeregistration'] = 'unsubscribeLimit';


// Onsubmit callback
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['onsubmit_callback'][] = array('tl_calendar_event_booking', 'adjustBookingDate');


// Operations
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations']['registrations'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['registrations'],
    'href'  => 'do=calendar&table=tl_calendar_events_member',
    'icon'  => 'bundles/markocupiccalendareventbooking/icons/group.png',
);


// Street
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['street'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['street'],
    'exclude'   => true,
    'search'    => true,
    'sorting'   => true,
    'flag'      => 1,
    'inputType' => 'text',
    'eval'      => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
    'sql'       => "varchar(255) NOT NULL default ''",
);

// postal
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['postal'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['postal'],
    'exclude'   => true,
    'search'    => true,
    'inputType' => 'text',
    'eval'      => array('maxlength' => 32, 'tl_class' => 'w50'),
    'sql'       => "varchar(32) NOT NULL default ''",
);

// city
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['city'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['city'],
    'exclude'   => true,
    'search'    => true,
    'sorting'   => true,
    'flag'      => 1,
    'inputType' => 'text',
    'eval'      => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
    'sql'       => "varchar(255) NOT NULL default ''",
);

// Enable booking options
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['addBookingForm'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['addBookingForm'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
    'sql'       => "char(1) NOT NULL default ''",
);

// bookingEndDate
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingEndDate'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['bookingEndDate'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => array('rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'),
    'sql'       => "int(10) unsigned NULL",
);

// bookingStartDate
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingStartDate'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['bookingStartDate'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => array('rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'),
    'sql'       => "int(10) unsigned NULL",
);

// maxMembers
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxMembers'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['maxMembers'],
    'exclude'   => true,
    'search'    => true,
    'default'   => 0,
    'inputType' => 'text',
    'eval'      => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
);

// guestsPerMember
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxEscortsPerMember'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['maxEscortsPerMember'],
    'exclude'   => true,
    'search'    => true,
    'default'   => 0,
    'inputType' => 'text',
    'eval'      => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
    'sql'       => "smallint(5) unsigned NOT NULL default '0'",
);

// Email from name
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['emailFromName'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['emailFromName'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
    'sql'       => "varchar(255) NOT NULL default ''",
);

// Email from address
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['emailFrom'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['emailFrom'],
    'exclude'   => true,
    'search'    => true,
    'inputType' => 'text',
    'eval'      => array('mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'),
    'sql'       => "varchar(255) NOT NULL default ''",
);

// bookingConfirmationEmailBody
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingConfirmationEmailBody'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['bookingConfirmationEmailBody'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => array('tl_class' => 'm12 clr', 'mandatory' => true),
    'sql'       => "text NULL",
);

// enableNotificationCenter
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableNotificationCenter'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['enableNotificationCenter'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
    'sql'       => "char(1) NOT NULL default ''",
);

// eventBookingNotificationCenterIds
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationCenterIds'] = array(
    'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events']['eventBookingNotificationCenterIds'],
    'exclude'    => true,
    'search'     => true,
    'inputType'  => 'select',
    'foreignKey' => 'tl_nc_notification.title',
    'eval'       => array('mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'),
    'sql'        => "blob NULL",
    'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
);

// eventBookingNotificationSender
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventBookingNotificationSender'] = array(
    'label'      => &$GLOBALS['TL_LANG']['tl_calendar_events']['eventBookingNotificationSender'],
    'exclude'    => true,
    'search'     => true,
    'inputType'  => 'select',
    'foreignKey' => 'tl_user.name',
    'eval'       => array('mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'clr'),
    'sql'        => "int(10) unsigned NOT NULL default '0'",
    'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
);

// enableDeregistration
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableDeregistration'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['enableDeregistration'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
    'sql'       => "char(1) NOT NULL default ''",
);

// unsubscribeLimit
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['unsubscribeLimit'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['unsubscribeLimit'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'select',
    'options'   => range(0, 720),
    'eval'      => array('rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'),
    'sql'       => "int(10) unsigned NOT NULL default '0'",
);


