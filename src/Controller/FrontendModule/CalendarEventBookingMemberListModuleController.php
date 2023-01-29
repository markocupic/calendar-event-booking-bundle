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
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(CalendarEventBookingMemberListModuleController::TYPE, category:'events', template: 'mod_calendar_event_booking_member_list_module')]
class CalendarEventBookingMemberListModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_member_list_module';

    public CalendarEventsModel|null $objEvent = null;

    // Adapters
    private Adapter $controller;
    private Adapter $eventMember;
    private Adapter $stringUtil;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly Connection $connection,
        private readonly EventRegistration $eventRegistration,
    ) {
        // Adapters
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

        $arrAllowedStates = $this->stringUtil->deserialize($model->cebb_memberListAllowedBookingStates, true);

        $t = $this->eventRegistration->getTable();

        // Get subscribed event members
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id')
            ->from($t, 't')
            ->where('t.pid = :pid')
            ->orderBy('t.dateAdded', 'ASC')
            ->addOrderBy('t.firstname', 'ASC')
            ->addOrderBy('t.city', 'ASC')
            ->setParameter('pid', $this->objEvent->id)
        ;

        if (!empty($arrAllowedStates)) {
            $qb = $qb->andWhere('t.bookingState IN (:arrAllowedStates)');
            $qb = $qb->setParameter('arrAllowedStates', $arrAllowedStates, Connection::PARAM_STR_ARRAY);
        }

        $result = $qb->executeQuery();

        $intRowCount = $result->rowCount();

        $i = 0;
        $strRows = '';

        while (!empty($arrAllowedStates) && false !== ($arrEventMember = $result->fetchAssociative())) {
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
