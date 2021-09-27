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
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Environment;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @FrontendModule(type=CalendarEventBookingEventBookingModuleController::TYPE, category="events", )
 */
class CalendarEventBookingEventBookingModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_event_booking_module';

    /**
     * @var EventRegistration
     */
    private $eventRegistration;

    /**
     * @var CalendarEventsModel
     */
    private $objEvent;

    /**
     * @var PageModel
     */
    private $objPage;

    public function __construct(EventRegistration $eventRegistration)
    {
        $this->eventRegistration = $eventRegistration;
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Is frontend
        if ($page instanceof PageModel && $this->get('contao.routing.scope_matcher')->isFrontendRequest($request)) {
            $this->objPage = $page;

            $showEmpty = true;

            $this->objEvent = $this->eventRegistration->getCurrentEventFromUrl();

            // Get the current event && return empty string if addBookingForm isn't set or event is not published
            if (null !== $this->objEvent) {
                if ($this->objEvent->addBookingForm && $this->objEvent->published) {
                    $showEmpty = false;
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

    /**
     * @return Response|null
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        /** @var System $systemAdapter */
        $systemAdapter = $this->get('contao.framework')->getAdapter(System::class);

        /** @var Environment $environmentAdapter */
        $environmentAdapter = $this->get('contao.framework')->getAdapter(Environment::class);

        /** @var StringUtil $stringUtilAdapter */
        $stringUtilAdapter = $this->get('contao.framework')->getAdapter(StringUtil::class);

        // Load language file
        $systemAdapter->loadLanguageFile('tl_calendar_events_member');

        if (null === $this->objEvent) {
            throw new PageNotFoundException('Page not found: '.$environmentAdapter->get('uri'));
        }

        // Overwrite the page title (see #2853 and #4955)
        if ('' !== $this->objEvent->title) {
            $this->objPage->pageTitle = strip_tags($stringUtilAdapter->stripInsertTags($this->objEvent->title));
        }

        $case = $this->eventRegistration->getRegistrationState($this->objEvent);

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

        $template->case = $case;
        $template->countBookings = $template->bookingCount = $this->eventRegistration->getBookingCount($this->objEvent);
        $template->bookingMin = $this->eventRegistration->getBookingMin($this->objEvent);
        $template->event = $this->objEvent;
        $template->user = $this->eventRegistration->getLoggedInFrontendUser();
        $template->model = $model;

        return $template->getResponse();
    }
}
