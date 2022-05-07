<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Helper;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;

class Event
{
    private ContaoFramework $framework;

    // Adapters
    private Adapter $config;
    private Adapter $input;
    private Adapter $eventsModel;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;

        // Adapters
        $this->config = $this->framework->getAdapter(Config::class);
        $this->eventsModel = $this->framework->getAdapter(CalendarEventsModel::class);
        $this->input = $this->framework->getAdapter(Input::class);
    }

    public function getEventFromCurrentUrl(): ?CalendarEventsModel
    {
        // Set the item from the auto_item parameter
        if (!isset($_GET['events']) && $this->config->get('useAutoItem') && isset($_GET['auto_item'])) {
            $this->input->setGet('events', $this->input->get('auto_item'));
        }

        // Return an empty string if "events" is not set
        if ('' !== $this->input->get('events')) {
            if (null !== ($objEvent = $this->eventsModel->findByIdOrAlias($this->input->get('events')))) {
                return $objEvent;
            }
        }

        return null;
    }
}
