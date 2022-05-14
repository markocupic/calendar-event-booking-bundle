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

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber\EventSubscriber;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @FrontendModule(type=CalendarEventBookingMemberListModuleController::TYPE, category="events")
 */
class CalendarEventBookingMemberListModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_member_list_module';

    public ?CalendarEventsModel $objEvent = null;

    private ContaoFramework $framework;
    private ScopeMatcher $scopeMatcher;
    private Connection $connection;
    private EventSubscriber $eventSubscriber;

    // Adapters
    private Adapter $controller;
    private Adapter $eventMember;

    /**
     * CalendarEventBookingMemberListModuleController constructor.
     */
    public function __construct(ContaoFramework $framework, ScopeMatcher $scopeMatcher, Connection $connection, EventSubscriber $eventSubscriber)
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->connection = $connection;
        $this->eventSubscriber = $eventSubscriber;

        // Adapters
        $this->eventMember = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $this->controller = $this->framework->getAdapter(Controller::class);
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
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        // Load language
        $this->controller->loadLanguageFile($this->eventSubscriber->getTable());

        $t = $this->eventSubscriber->getTable();

        // Get subscribed event members
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id')
            ->from($t, 't')
            ->where('t.pid = :pid')
            ->andWhere('t.bookingState = :bookingState')
            ->orderBy('t.dateAdded', 'ASC')
            ->addOrderBy('t.firstname', 'ASC')
            ->addOrderBy('t.city', 'ASC')
            ->setParameter('pid', $this->objEvent->id)
            ->setParameter('bookingState', BookingState::STATE_CONFIRMED)
        ;

        $result = $qb->executeQuery();

        $intRowCount = $result->rowCount();

        $i = 0;
        $strRows = '';

        while (false !== ($arrEventMember = $result->fetchAssociative())) {
            $partial = new FrontendTemplate($model->cebb_memberListPartialTemplate);

            $calendarEventsMemberModel = $this->eventMember->findByPk($arrEventMember['id']);
            $partial->model = $calendarEventsMemberModel;

            // Row class
            $partial->rowClass = $this->getRowClass($i, $intRowCount);

            $strRows .= $partial->parse();

            ++$i;
        }

        // Add partial html to the parent template
        if ($i) {
            $template->members = $strRows;
        }

        // Add the event model to the parent template
        $template->event = $this->objEvent;

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
