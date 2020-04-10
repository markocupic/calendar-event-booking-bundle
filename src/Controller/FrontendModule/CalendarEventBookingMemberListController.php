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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CalendarEventBookingMemberListController
 * @package Markocupic\CalendarEventBookingBundle\Controller\FrontendModule
 * @FrontendModule(category="events", type="calendar_event_booking_member_list_module")
 */
class CalendarEventBookingMemberListController extends AbstractFrontendModuleController
{

    /**
     * @var ContaoFramework
     */
    protected $framework;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ScopeMatcher
     */
    protected $scopeMatcher;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var CalendarEventsModel
     */
    protected $objEvent;

    /**
     * CalendarEventBookingMemberListController constructor.
     * @param ContaoFramework $framework
     * @param RequestStack $requestStack
     * @param ScopeMatcher $scopeMatcher
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct(ContaoFramework $framework, RequestStack $requestStack, ScopeMatcher $scopeMatcher, Connection $connection)
    {
        $this->framework = $framework;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->connection = $connection;
    }

    /**
     * Like generate-method in past contao modules
     * ! This method is optional and can be used, if the response should contain an empty string only
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
            // Set adapters
            $configAdapter = $this->framework->getAdapter(Config::class);
            $inputAdapter = $this->framework->getAdapter(Input::class);
            $calendarEventsModelAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

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

            if ($showEmpty)
            {
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

        // Call the parent method
        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * Like compile-method in past contao modules
     * @param Template $template
     * @param ModuleModel $model
     * @param Request $request
     * @return null|Response
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        // Set adapters
        $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);

        // Load language
        Controller::loadLanguageFile('tl_calendar_events_member');

        /** @var  Doctrine\DBAL\Driver\PDOStatement $results */
        $results = $this->getSignedUpMembers(intval($this->objEvent->id));
        $intRowCount = $results->rowCount();

        $i = 0;
        $strRows = '';
        while (false !== ($arrEventMember = $results->fetch()))
        {
            /** @var  FrontendTemplate $partial */
            $partial = new FrontendTemplate($model->calendar_event_booking_member_list_partial_template);

            /** @var CalendarEventsMemberModel $calendarEventsMemberModel */
            $calendarEventsMemberModel = $calendarEventsMemberModelAdapter->findByPk($arrEventMember['id']);
            $partial->model = $calendarEventsMemberModel;

            // Row class
            $partial->rowClass = $this->getRowClass($i, $intRowCount);

            $strRows .= $partial->parse();
            $i++;
        }

        // Add partial html to the parent template
        $template->members = $strRows;

        // Add the event model to the parent template
        $template->event = $this->objEvent;

        return $template->getResponse();
    }

    /**
     * Get signed up members of current event
     * @param int $id
     * @return \Doctrine\DBAL\Driver\PDOStatement
     */
    protected function getSignedUpMembers(int $id): \Doctrine\DBAL\Driver\PDOStatement
    {
        /** @var  Doctrine\DBAL\Query\QueryBuilder $qb */
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id')
            ->from('tl_calendar_events_member', 't')
            ->where('t.pid = :pid')
            ->orderBy('t.lastname', 'ASC')
            ->addOrderBy('t.firstname', 'ASC')
            ->addOrderBy('t.city', 'ASC')
            ->setParameter('pid', $id);
        return $qb->execute();
    }

    /**
     * @param int $i
     * @param int $intRowsTotal
     * @return string
     */
    protected function getRowClass(int $i, int $intRowsTotal): string
    {
        $rowFirst = ($i === 0) ? ' row_first' : '';
        $rowLast = ($i === $intRowsTotal - 1) ? ' row_last' : '';
        $evenOrOdd = ($i % 2) ? ' odd' : ' even';
        return sprintf('row_%s%s%s%s', $i, $rowFirst, $rowLast, $evenOrOdd);
    }

    /**
     * Identify the Contao scope (TL_MODE) of the current request
     * @return bool
     */
    protected function isFrontend(): bool
    {
        return $this->scopeMatcher->isFrontendRequest($this->requestStack->getCurrentRequest());
    }
}
