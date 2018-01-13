<?php

/**
 * @copyright  Marko Cupic 2018
 * @author     Marko Cupic, Oberkirch, Switzerland ->  mailto: m.cupic@gmx.ch
 * @package    markocupic/calendar-event-booking-bundle
 * @license    GNU/LGPL
 */

namespace Markocupic\CalendarEventBookingBundle;

use Contao\BackendTemplate;
use Contao\CalendarEventsMemberModel;
use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Patchwork\Utf8;

/**
 * Class ModuleEventBooking
 * @package Markocupic\CalendarEventBookingBundle
 */
class ModuleEventBooking extends Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_eventbooking';


    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['eventbooking'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['events']) && Config::get('useAutoItem') && isset($_GET['auto_item']))
        {
            Input::setGet('events', Input::get('auto_item'));
        }

        // Do not index or cache the page if no event has been specified
        if (!Input::get('events'))
        {
            /** @var PageModel $objPage */
            global $objPage;

            $objPage->noSearch = 1;
            $objPage->cache = 0;

            return '';
        }

        // Get the current event && return empty string if addBookingForm isn't set or event is not published
        $objEvent = CalendarEventsModel::findByIdOrAlias(\Input::get('events'));
        if ($objEvent !== null)
        {
            if (!$objEvent->addBookingForm || !$objEvent->published)
            {
                return '';
            }
        }


        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        global $objPage;
        Controller::loadLanguageFile('tl_calendar_events_member');

        $this->Template->event = '';
        $this->Template->referer = 'javascript:history.go(-1)';
        $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

        // Get the current event
        $objEvent = CalendarEventsModel::findByIdOrAlias(\Input::get('events'));

        if (null === $objEvent)
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        // Overwrite the page title (see #2853 and #4955)
        if ($objEvent->title != '')
        {
            $objPage->pageTitle = strip_tags(StringUtil::stripInsertTags($objEvent->title));
        }

        $this->Template->setData($objEvent->row());
        $this->Template->id = $this->id;

        // Count bookings if event is not fully booked
        $countBookings = CalendarEventsMemberModel::countBy('pid', $objEvent->id);

        if ($objEvent->bookingStartDate > 0 && $objEvent->bookingStartDate > time())
        {
            // User has to wait. Booking is not possible yet
            $case = 'bookingNotYetPossible';
        }
        elseif ($objEvent->bookingEndDate > 0 && $objEvent->bookingEndDate < time())
        {
            // User is to late the sign in deadline has proceeded
            $case = 'bookingNoLongerPossible';
        }
        elseif ($countBookings > 0 && $objEvent->maxMembers > 0 && $countBookings >= $objEvent->maxMembers)
        {
            // Check if event is  fully booked
            $case = 'eventFullyBooked';
        }
        else
        {
            $case = 'bookingPossible';
        }

        $this->Template->case = $case;


        switch ($case)
        {
            case 'bookingPossible':
                if ($this->form > 0)
                {
                    $this->Template->form = $this->form;
                }
                break;
            case 'bookingNotYetPossible':
                break;
            case 'bookingNoLongerPossible':
                break;
            case 'eventFullyBooked':
                break;

        }
    }
}
