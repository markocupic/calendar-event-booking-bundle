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
use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CalendarEventBookingMemberListModuleController.
 *
 * @FrontendModule(category="events", type="calendar_event_booking_member_list_module")
 */
class CalendarEventBookingMemberListModuleController extends AbstractFrontendModuleController
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var CalendarEventsModel
     */
    protected $objEvent;

    /**
     * CalendarEventBookingMemberListModuleController constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Is frontend
        if ($page instanceof PageModel && $this->get('contao.routing.scope_matcher')->isFrontendRequest($request)) {
            // Set adapters
            $configAdapter = $this->get('contao.framework')->getAdapter(Config::class);
            $inputAdapter = $this->get('contao.framework')->getAdapter(Input::class);
            $calendarEventsModelAdapter = $this->get('contao.framework')->getAdapter(CalendarEventsModel::class);

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
        /** @var CalendarEventsMemberModel $calendarEventsMemberModelAdapter */
        $calendarEventsMemberModelAdapter = $this->get('contao.framework')->getAdapter(CalendarEventsMemberModel::class);

        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->get('contao.framework')->getAdapter(Controller::class);

        // Load language
        $controllerAdapter->loadLanguageFile('tl_calendar_events_member');

        /** @var PDOStatement $results */
        $results = $this->getSignedUpMembers((int) ($this->objEvent->id));
        $intRowCount = $results->rowCount();

        $i = 0;
        $strRows = '';

        while (false !== ($arrEventMember = $results->fetch())) {
            /** @var FrontendTemplate $partial */
            $partial = new FrontendTemplate($model->calendar_event_booking_member_list_partial_template);

            /** @var CalendarEventsMemberModel $calendarEventsMemberModel */
            $calendarEventsMemberModel = $calendarEventsMemberModelAdapter->findByPk($arrEventMember['id']);
            $partial->model = $calendarEventsMemberModel;

            // Row class
            $partial->rowClass = $this->getRowClass($i, $intRowCount);

            $strRows .= $partial->parse();
            ++$i;
        }

        // Add partial html to the parent template
        $template->members = $strRows;

        // Add the event model to the parent template
        $template->event = $this->objEvent;

        return $template->getResponse();
    }

    /**
     * Get signed up members of current event.
     */
    protected function getSignedUpMembers(int $id): PDOStatement
    {
        /** @var QueryBuilder $qb */
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id')
            ->from('tl_calendar_events_member', 't')
            ->where('t.pid = :pid')
            ->orderBy('t.lastname', 'ASC')
            ->addOrderBy('t.firstname', 'ASC')
            ->addOrderBy('t.city', 'ASC')
            ->setParameter('pid', $id)
        ;

        return $qb->execute();
    }

    protected function getRowClass(int $i, int $intRowsTotal): string
    {
        $rowFirst = 0 === $i ? ' row_first' : '';
        $rowLast = $i === $intRowsTotal - 1 ? ' row_last' : '';
        $evenOrOdd = $i % 2 ? ' odd' : ' even';

        return sprintf('row_%s%s%s%s', $i, $rowFirst, $rowLast, $evenOrOdd);
    }
}
