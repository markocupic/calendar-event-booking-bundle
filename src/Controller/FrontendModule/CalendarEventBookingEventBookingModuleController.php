<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2020 <m.cupic@gmx.ch>
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
use Contao\FormModel;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Markocupic\CalendarEventBookingBundle\Helper\BookingForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

/**
 * Class CalendarEventBookingEventBookingModuleController.
 *
 * @FrontendModule(category="events", type="calendar_event_booking_event_booking_module")
 */
class CalendarEventBookingEventBookingModuleController extends AbstractFrontendModuleController
{
    /**
     * @var BookingForm
     */
    protected $bookingFormHelper;

    /**
     * @var CalendarEventsModel
     */
    protected $objEvent;

    /**
     * @var PageModel
     */
    protected $objPage;

    /**
     * @var MemberModel|null
     */
    protected $objUser;

    /**
     * CalendarEventBookingEventBookingModuleController constructor.
     */
    public function __construct(BookingForm $bookingFormHelper)
    {
        $this->bookingFormHelper = $bookingFormHelper;
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // If is frontend:
        if ($page instanceof PageModel && $this->get('contao.routing.scope_matcher')->isFrontendRequest($request)) {
            $this->objUser = $this->bookingFormHelper->getLoggedInUser();

            $this->objPage = $page;

            $showEmpty = false;

            // Get the current event
            if (null === ($this->objEvent = $this->bookingFormHelper->getEventFromUrl())) {
                $showEmpty = true;
            }

            // Return empty string if logged in user has no admin privilegies and addBookingForm isn't set or event is not published
            if (null !== $this->objEvent && false === $this->bookingFormHelper->loggedInUserIsAdmin($model)) {
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
        $services['security.helper'] = Security::class;

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

        /** @var FormModel $formModelAdapter */
        $formModelAdapter = $this->get('contao.framework')->getAdapter(FormModel::class);

        // Load language file
        $systemAdapter->loadLanguageFile('tl_calendar_events_member');

        if (null === $this->objEvent) {
            throw new PageNotFoundException('Page not found: '.$environmentAdapter->get('uri'));
        }

        // Overwrite the page title (see #2853 and #4955)
        if ('' !== $this->objEvent->title) {
            $this->objPage->pageTitle = strip_tags($stringUtilAdapter->stripInsertTags($this->objEvent->title));
        }

        // Check if logged in frontend user is admin
        $loggedInUserIsAdmin = $this->bookingFormHelper->loggedInUserIsAdmin($model);

        // Count bookings if event is not fully booked
        $template->countBookings = $this->bookingFormHelper->getNumberOfBookings();

        // Add event model to template
        $template->event = $this->objEvent;

        // Add logged in frontend user (if there is one) to template
        $template->objuser = $this->objUser;

        // Check if logged in frontend user has admin privilegies
        $template->loggedInUserIsAdmin = $loggedInUserIsAdmin;

        // Get the case
        $case = $this->bookingFormHelper->getCase($model);
        $template->case = $case;

        switch ($case) {
            case 'bookingPossible':
                if ($model->form > 0) {
                    if (null !== ($objForm = $formModelAdapter->findByPk($model->form))) {
                        $template->form = $objForm->id;
                    }
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
