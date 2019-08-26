<?php

/**
 * @copyright  Marko Cupic 2019
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */


/**
 * Table tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['eventbooking'] = '{title_legend},name,headline,type;{form_legend},form;{notification_center_legend:hide},enableNotificationCenter;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['unsubscribefromevent'] = '{title_legend},name,headline,type;{notification_center_legend:hide},unsubscribeFromEventNotificationIds;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';


// unsubscribeFromEventNotificationIds
$GLOBALS['TL_DCA']['tl_module']['fields']['unsubscribeFromEventNotificationIds'] = array(
    'label'      => &$GLOBALS['TL_LANG']['tl_module']['unsubscribeFromEventNotificationIds'],
    'exclude'    => true,
    'search'     => true,
    'inputType'  => 'select',
    'foreignKey' => 'tl_nc_notification.title',
    'eval'       => array('mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true, 'tl_class' => 'clr'),
    'sql'        => "blob NULL",
    'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
);
