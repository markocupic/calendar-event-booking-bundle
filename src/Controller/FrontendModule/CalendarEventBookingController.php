<?php

declare(strict_types=1);

/**
 * Calendar Event Booking Bundle Extension for Contao CMS
 * Copyright (c) 2008-2020 Marko Cupic
 * @package Markocupic\CalendarEventBookingBundle
 * @author Marko Cupic m.cupic@gmx.ch, 2020
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsMemberModel;
use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Environment;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;

/**
 * Class CalendarEventBookingController
 * @package Markocupic\CalendarEventBookingBundle\Controller\FrontendModule
 * @FrontendModule(category="events", type="eventbooking")
 */
class CalendarEventBookingController extends AbstractFrontendModuleController
{

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var CalendarEventsModel
     */
    protected $objEvent;

    /**
     * @var PageModel
     */
    protected $objPage;

    /**
     * CalendarEventBookingController constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param Request $request
     * @param ModuleModel $model
     * @param string $section
     * @param array|null $classes
     * @param PageModel|null $page
     * @return Response
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Return empty string, if user is not logged in as a frontend user
        if ($this->isFrontend())
        {
            /** @var Config $configAdapter */
            $configAdapter = $this->get('contao.framework')->getAdapter(Config::class);

            /** @var Input $inputAdapter */
            $inputAdapter = $this->get('contao.framework')->getAdapter(Input::class);

            /** @var CalendarEventsModel $calendarEventsModelAdapter */
            $calendarEventsModelAdapter = $this->get('contao.framework')->getAdapter(CalendarEventsModel::class);

            $this->objPage = $page;

            $showEmpty = false;

            // Set the item from the auto_item parameter
            if (!isset($_GET['events']) && $configAdapter->get('useAutoItem') && isset($_GET['auto_item']))
            {
                $inputAdapter->setGet('events', $inputAdapter->get('auto_item'));
            }

            // Return an empty string if "events" is not set
            if (!$inputAdapter->get('events'))
            {
                $showEmpty = true;
            }
            elseif (null === ($this->objEvent = $calendarEventsModelAdapter->findByIdOrAlias($inputAdapter->get('events'))))
            {
                $showEmpty = true;
            }

            // Get the current event && return empty string if addBookingForm isn't set or event is not published
            if ($this->objEvent !== null)
            {
                if (!$this->objEvent->addBookingForm || !$this->objEvent->published)
                {
                    $showEmpty = true;
                }
            }

            if ($showEmpty)
            {
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

        // Call the parent method
        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['contao.framework'] = ContaoFramework::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    /**
     * @param Template $template
     * @param ModuleModel $model
     * @param Request $request
     * @return null|Response
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->get('contao.framework')->getAdapter(Controller::class);

        /** @var Environment $environmentAdapter */
        $environmentAdapter = $this->get('contao.framework')->getAdapter(Environment::class);

        /** @var StringUtil $stringUtilAdapter */
        $stringUtilAdapter = $this->get('contao.framework')->getAdapter(StringUtil::class);

        /** @var CalendarEventsMemberModel $calendarEventsMemberModelAdaper */
        $calendarEventsMemberModelAdaper = $this->get('contao.framework')->getAdapter(CalendarEventsMemberModel::class);

        // Load language
        $controllerAdapter->loadLanguageFile('tl_calendar_events_member');

        $template->event = '';
        $template->referer = 'javascript:history.go(-1)';
        $template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

        if (null === $this->objEvent)
        {
            throw new PageNotFoundException('Page not found: ' . $environmentAdapter->get('uri'));
        }

        // Overwrite the page title (see #2853 and #4955)
        if ($this->objEvent->title != '')
        {
            $this->objPage->pageTitle = strip_tags($stringUtilAdapter->stripInsertTags($this->objEvent->title));
        }

        $template->id = $model->id;

        // Count bookings if event is not fully booked
        $countBookings = $calendarEventsMemberModelAdaper->countBy('pid', $this->objEvent->id);

        // countBookings for template
        $arrTemplateData = array_merge($this->objEvent->row(), ['countBookings' => $countBookings]);
        $template->setData($arrTemplateData);

        if ($this->objEvent->bookingStartDate > 0 && $this->objEvent->bookingStartDate > time())
        {
            // User has to wait. Booking is not possible yet
            $case = 'bookingNotYetPossible';
        }
        elseif ($this->objEvent->bookingEndDate > 0 && $this->objEvent->bookingEndDate < time())
        {
            // User is to late the sign in deadline has proceeded
            $case = 'bookingNoLongerPossible';
        }
        elseif ($countBookings > 0 && $this->objEvent->maxMembers > 0 && $countBookings >= $this->objEvent->maxMembers)
        {
            // Check if event is  fully booked
            $case = 'eventFullyBooked';
        }
        else
        {
            $case = 'bookingPossible';
        }

        $template->case = $case;

        switch ($case)
        {
            case 'bookingPossible':
                if ($model->form > 0)
                {
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

    /**
     * Identify the Contao scope (TL_MODE) of the current request
     * @return bool
     */
    protected function isFrontend(): bool
    {
        return $this->get('contao.routing.scope_matcher')->isFrontendRequest($this->requestStack->getCurrentRequest());
    }
}
