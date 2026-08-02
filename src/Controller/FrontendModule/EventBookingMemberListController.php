<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Request\EventUrlResolver;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(EventBookingMemberListController::TYPE, category: 'events')]
class EventBookingMemberListController extends AbstractFrontendModuleController
{
    public const string TYPE = 'event_booking_member_list';

    private CalendarEventsModel|null $calEvent = null;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $columnCache = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly FigureUtil $figureUtil,
        private readonly EventUrlResolver $eventUrlResolver,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $showEmpty = true;

            $this->calEvent = $this->eventUrlResolver->resolve();

            // Get the current event && return empty string
            // if enableBookingForm isn't set, or the event is not published
            if (null !== $this->calEvent) {
                if ($this->calEvent->enableBookingForm && $this->calEvent->published) {
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
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $controllerAdapter = $this->getContaoAdapter(Controller::class);

        // Load language
        $controllerAdapter->loadLanguageFile(CalendarEventsMemberModel::getTable());

        $arrWhere = [];

        if ($model->ceb_modMemberList_enableBookingStatusFilter) {
            $arrWhere = StringUtil::deserialize($model->ceb_modMemberList_bookingStatusFilter, true);
        }

        $arrOrder = StringUtil::deserialize($model->ceb_modMemberList_sorting, true);
        $arrOrder = empty($arrOrder) ? ['dateAdded::DESC'] : $arrOrder;

        $rows = $this->getBookings($this->calEvent->id, $arrWhere, $arrOrder);
        $rowCount = \count($rows);

        $i = 0;

        $bookings = [];

        foreach ($rows as $row) {
            $row['rowClass'] = $this->getRowClass($i, $rowCount);
            $bookings[] = $row;
            ++$i;
        }

        $template->set('bookings', $bookings);

        // Add the event model to the parent template
        $template->set('event', $this->calEvent);
        $template->set('calendar', $this->calEvent->getRelated('pid'));

        if ($model->ceb_addImage && $this->calEvent->addImage) {
            $figure = $this->figureUtil->buildFigure($this->calEvent->row());

            if (null !== $figure) {
                $template->set('addImage', true);
                $template->set('figure', $figure);
            }
        }

        return $template->getResponse();
    }

    protected function columnExists(string $table, string $column): bool
    {
        if (!isset($this->columnCache[$table])) {
            $this->columnCache[$table] = $this->connection->createSchemaManager()->listTableColumns($table);
        }

        return \array_key_exists(strtolower($column), $this->columnCache[$table]);
    }

    protected function getBookings(int $eventId, array $arrWhere, array $arrOrder): array
    {
        $t = CalendarEventsMemberModel::getTable();

        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($t, 't')
            ->where('t.pid = :pid')
            ->setParameter('pid', $eventId, ParameterType::INTEGER)
        ;

        // Collect the (optional) booking status conditions, combine them with OR and
        // AND the whole group onto the event restriction. This keeps the event scope
        // intact even when a status filter is active (and when it is not).
        $statusConditions = [];

        foreach ($arrWhere as $strWhere) {
            [$col, $value] = StringUtil::trimsplit('::', $strWhere);

            if (!$this->columnExists($t, $col)) {
                continue;
            }

            $value = 'true' === $value ? 1 : $value;
            $value = 'false' === $value ? 0 : $value;

            $statusConditions[] = "t.$col = :$col";
            $qb->setParameter($col, $value);
        }

        if ($statusConditions) {
            $qb->andWhere('('.implode(' OR ', $statusConditions).')');
        }

        foreach ($arrOrder as $strOrder) {
            [$col, $direction] = StringUtil::trimsplit('::', $strOrder);

            // Whitelist the column against the actual table schema and restrict the
            // direction to ASC/DESC to prevent SQL injection via the module config.
            if (!$this->columnExists($t, $col)) {
                continue;
            }

            $direction = 'DESC' === strtoupper((string) $direction) ? 'DESC' : 'ASC';

            $qb->addOrderBy("t.$col", $direction);
        }

        return $qb->fetchAllAssociative();
    }

    protected function getRowClass(int $i, int $intRowsTotal): string
    {
        $rowFirst = 0 === $i ? ' row_first' : '';
        $rowLast = $i === $intRowsTotal - 1 ? ' row_last' : '';
        $evenOrOdd = $i % 2 ? ' odd' : ' even';

        return \sprintf('row_%s%s%s%s', $i, $rowFirst, $rowLast, $evenOrOdd);
    }
}
