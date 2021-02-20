<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Environment;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CalendarEventBookingEventBookingModuleController.
 *
 * @FrontendModule(category="events", type="calendar_event_booking_event_booking_module")
 */
class CalendarEventBookingEventBookingModuleController extends AbstractFrontendModuleController
{
    /**
     * @var CalendarEventsModel
     */
    protected $objEvent;

    /**
     * @var PageModel
     */
    protected $objPage;

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Is frontend
        if ($page instanceof PageModel && $this->get('contao.routing.scope_matcher')->isFrontendRequest($request)) {
            /** @var Config $configAdapter */
            $configAdapter = $this->get('contao.framework')->getAdapter(Config::class);

            /** @var Input $inputAdapter */
            $inputAdapter = $this->get('contao.framework')->getAdapter(Input::class);

            /** @var CalendarEventsModel $calendarEventsModelAdapter */
            $calendarEventsModelAdapter = $this->get('contao.framework')->getAdapter(CalendarEventsModel::class);

            $this->objPage = $page;

            $showEmpty = false;

            // Set the item from the auto_item parameter
            if (!isset($_GET['events']) && $configAdapter->get('useAutoItem') && isset($_GET['auto_item'])) {
                $inputAdapter->setGet('events', $inputAdapter->get('auto_item'));
            }

            // Return an empty string if "events" is not set
            if (!$inputAdapter->get('events')) {
                $showEmpty = true;
            } elseif (null === ($this->objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events')))) {
                $showEmpty = true;
            }

            // Get the current event && return empty string if addBookingForm isn't set or event is not published
            if (null !== $this->objEvent) {
                if (!$this->objEvent->addBookingForm || !$this->objEvent->published) {
                    $showEmpty = true;
                }
            }

            if ($showEmpty) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

        // Call the parent method
        return parent::__invoke($request, $model, $section, $classes);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['contao.framework'] = ContaoFramework::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        /** @var System $systemAdapter */
        $systemAdapter = $this->get('contao.framework')->getAdapter(System::class);

        /** @var Environment $environmentAdapter */
        $environmentAdapter = $this->get('contao.framework')->getAdapter(Environment::class);

        /** @var StringUtil $stringUtilAdapter */
        $stringUtilAdapter = $this->get('contao.framework')->getAdapter(StringUtil::class);

        /** @var CalendarEventsMemberModel $calendarEventsMemberModelAdaper */
        $calendarEventsMemberModelAdaper = $this->get('contao.framework')->getAdapter(CalendarEventsMemberModel::class);

        // Load language file
        $systemAdapter->loadLanguageFile('tl_calendar_events_member');

        if (null === $this->objEvent) {
            throw new PageNotFoundException('Page not found: '.$environmentAdapter->get('uri'));
        }

        // Overwrite the page title (see #2853 and #4955)
        if ('' !== $this->objEvent->title) {
            $this->objPage->pageTitle = strip_tags($stringUtilAdapter->stripInsertTags($this->objEvent->title));
        }

        // Count bookings if event is not fully booked
        $countBookings = $calendarEventsMemberModelAdaper->countBy('pid', $this->objEvent->id);
        $template->countBookings = $countBookings;

        // Add event model to template
        $template->event = $this->objEvent;

        if ($this->objEvent->bookingStartDate > 0 && $this->objEvent->bookingStartDate > time()) {
            // User has to wait. Booking is not possible yet
            $case = 'bookingNotYetPossible';
        } elseif ($this->objEvent->bookingEndDate > 0 && $this->objEvent->bookingEndDate < time()) {
            // User is to late the sign in deadline has proceeded
            $case = 'bookingNoLongerPossible';
        } elseif ($countBookings > 0 && $this->objEvent->maxMembers > 0 && $countBookings >= $this->objEvent->maxMembers) {
            // Check if event is  fully booked
            $case = 'eventFullyBooked';
        } else {
            $case = 'bookingPossible';
        }

        $template->case = $case;

        switch ($case) {
            case 'bookingPossible':
                if ($model->form > 0) {
                    $template->form = $model->form;
                }
                break;

            case 'bookingNotYetPossible':
                break;

            case 'bookingNoLongerPossible':
                break;

            case 'eventFullyBooked':
                break;
        }

        return $template->getResponse();
    }
}
