<?php
use Contao\Message;

/**
 * Table tl_calendar_events
 */

// Table config
$GLOBALS['TL_DCA']['tl_calendar']['config']['ctable'][] = 'tl_calendar_events_member';


// Palettes
$defaultPalette = $GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['default'];
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['default'] = str_replace('{date_legend', '{booking_options_legend:hide},addBookingForm;{date_legend', $defaultPalette);

// Subpalettes & __selector__
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'addBookingForm';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['addBookingForm'] = 'maxMembers,maxEscortsPerMember,bookingStartDate,bookingEndDate,emailFromName,emailFrom,bookingConfirmationEmailBody;';

// Onsubmit callback
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['onsubmit_callback'][] = array('tl_calendar_event_booking', 'adjustBookingDate');

// Operations
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations']['registrations'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['registrations'],
    'href' => 'do=calendar&table=tl_calendar_events_member',
    'icon' => 'bundles/markocupiccalendareventbooking/icons/group.png'
);


// Enable booking options
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['addBookingForm'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['addBookingForm'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => array('submitOnChange' => true, 'tl_class' => 'clr m12'),
    'sql' => "char(1) NOT NULL default ''"
);


$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingEndDate'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['bookingEndDate'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => array('rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'),
    'sql' => "int(10) unsigned NULL"
);

// bookingStartDate
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingStartDate'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['bookingStartDate'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => array('rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'),
    'sql' => "int(10) unsigned NULL"
);

// maxMembers
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxMembers'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['maxMembers'],
    'exclude' => true,
    'search' => true,
    'default' => 0,
    'inputType' => 'text',
    'eval' => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
);

// guestsPerMember
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['maxEscortsPerMember'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['maxEscortsPerMember'],
    'exclude' => true,
    'search' => true,
    'default' => 0,
    'inputType' => 'text',
    'eval' => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
);

// Email from name
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['emailFromName'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['emailFromName'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
    'sql' => "varchar(255) NOT NULL default ''"
);

// Email from address
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['emailFrom'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['emailFrom'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'text',
    'eval' => array('mandatory' => true, 'maxlength' => 255, 'rgxp' => 'email', 'decodeEntities' => true, 'tl_class' => 'w50'),
    'sql' => "varchar(255) NOT NULL default ''"
);

// bookingConfirmationEmailBody
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['bookingConfirmationEmailBody'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['bookingConfirmationEmailBody'],
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => array('tl_class' => 'm12 clr', 'mandatory' => true),
    'sql' => "text NULL"
);


/**
 * Class tl_calendar_event_booking
 */
class tl_calendar_event_booking extends tl_calendar_events
{

    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Adjust bookingStartDate and  bookingStartDate
     *
     * @param DataContainer $dc
     */
    public function adjustBookingDate(DataContainer $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }

        $arrSet['bookingStartDate'] = $dc->activeRecord->bookingStartDate;
        $arrSet['bookingEndDate'] = $dc->activeRecord->bookingEndDate;

        // Set end date
        if (strlen($dc->activeRecord->bookingEndDate))
        {
            if ($dc->activeRecord->bookingEndDate < $dc->activeRecord->bookingStartDate)
            {
                $arrSet['bookingEndDate'] = $dc->activeRecord->bookingStartDate;
                Message::addInfo('Das Enddatum für den Buchungszeitraum wurde angepasst.', TL_MODE);
            }
        }


        $this->Database->prepare("UPDATE tl_calendar_events %s WHERE id=?")->set($arrSet)->execute($dc->id);
    }

}