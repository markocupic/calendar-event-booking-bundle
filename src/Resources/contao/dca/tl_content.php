<?php
/**
 * Created by PhpStorm.
 * User: Marko
 * Date: 18.10.2016
 * Time: 14:07
 */

if (Input::get('do') == 'calendar_sac')
{
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_calendar_events';
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = array('tl_content_calendar', 'checkPermission');
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = array('tl_content_calendar', 'generateFeed');
    $GLOBALS['TL_DCA']['tl_content']['list']['operations']['toggle']['button_callback'] = array('tl_content_calendar', 'toggleIcon');
}

$GLOBALS['TL_DCA']['tl_content']['palettes']['ce_user_portrait'] = 'name,type,headline;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{invisible_legend:hide},invisible,start,stop';
