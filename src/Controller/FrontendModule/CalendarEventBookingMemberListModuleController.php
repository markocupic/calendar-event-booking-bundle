<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventFactory;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventBooking\Template\AddTemplateData;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(CalendarEventBookingMemberListModuleController::TYPE, category:'events', template: 'mod_calendar_event_booking_member_list_module')]
class CalendarEventBookingMemberListModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_member_list_module';

    public CalendarEventsModel|null $objEvent = null;

    private Adapter $controller;
    private Adapter $eventMember;
    private Adapter $stringUtil;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly Connection $connection,
        private readonly EventFactory $eventFactory,
        private readonly EventRegistration $eventRegistration,
        private readonly AddTemplateData $addTemplateData,
    ) {
        $this->eventMember = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $this->controller = $this->framework->getAdapter(Controller::class);
        $this->stringUtil = $this->framework->getAdapter(StringUtil::class);
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Is frontend
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $showEmpty = true;

            $this->objEvent = EventConfig::getEventFromCurrentUrl();

            // Get the current event && return empty string if activateBookingForm isn't set or event is not published
            if (null !== $this->objEvent) {
                if ($this->objEvent->activateBookingForm && $this->objEvent->published) {
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

    /**
     * @throws Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response|null
    {
        // Load language
        $this->controller->loadLanguageFile($this->eventRegistration->getTable());

        // Get the event configuration
        $eventConfig = $this->eventFactory->create($this->objEvent);

        $arrAllowedStates = $this->stringUtil->deserialize($model->cebb_memberListAllowedBookingStates, true);
        $arrOptions = [
            'order' => 'dateAdded ASC, firstname ASC, city ASC',
        ];

        $registrations = $eventConfig->getRegistrations($arrAllowedStates, $arrOptions);

        if (empty($registrations)) {
            return null;
        }

        $i = 0;
        $intRegCount = \count($registrations);
        $rows = [];

        foreach ($registrations as $registration) {
            $rows[] = [
                'model' => $registration,
                'row_class' => $this->getRowClass($i, $intRegCount),
            ];

            ++$i;
        }

        if ($i) {
            $template->rows = $rows;
        }

        // Add the event model to the parent template
        $template->event = $this->objEvent;

        // Augment template with more data
        $this->addTemplateData->addTemplateData($eventConfig, $template);

        return $template->getResponse();
    }

    protected function getRowClass(int $i, int $intRowsTotal): string
    {
        $rowFirst = 0 === $i ? ' row_first' : '';
        $rowLast = $i === $intRowsTotal - 1 ? ' row_last' : '';
        $evenOrOdd = $i % 2 ? ' odd' : ' even';

        return sprintf('row_%s%s%s%s', $i, $rowFirst, $rowLast, $evenOrOdd);
    }
}
