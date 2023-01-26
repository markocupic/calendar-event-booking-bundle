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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(CalendarEventBookingMemberListModuleController::TYPE, category:'events', template: 'mod_calendar_event_booking_member_list_module')]
class CalendarEventBookingMemberListModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'calendar_event_booking_member_list_module';

    private ?CalendarEventsModel $objEvent = null;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly Connection $connection,
        private readonly EventRegistration $eventRegistration,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Is frontend
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $showEmpty = true;

            $this->objEvent = $this->eventRegistration->getEventFromCurrentUrl();

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

    /**
     * @throws Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        // Load language
        $controllerAdapter->loadLanguageFile(CalendarEventBookingEventBookingModuleController::EVENT_SUBSCRIPTION_TABLE);

        $results = $this->getSignedUpMembers((int) ($this->objEvent->id));
        $intRowCount = $results->rowCount();

        $i = 0;
        $strRows = '';

        while (false !== ($arrEventMember = $results->fetchAssociative())) {
            $partial = new FrontendTemplate($model->calendarEventBookingMemberListPartialTemplate);

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
     *
     * @throws Exception
     *
     * @return Result
     */
    protected function getSignedUpMembers(int $id)
    {
        $t = CalendarEventBookingEventBookingModuleController::EVENT_SUBSCRIPTION_TABLE;

        $qb = $this->connection->createQueryBuilder();
        $qb->select('id')
            ->from($t, 't')
            ->where('t.pid = :pid')
            ->orderBy('t.lastname', 'ASC')
            ->addOrderBy('t.firstname', 'ASC')
            ->addOrderBy('t.city', 'ASC')
            ->setParameter('pid', $id)
        ;

        return $qb->executeQuery();
    }

    protected function getRowClass(int $i, int $intRowsTotal): string
    {
        $rowFirst = 0 === $i ? ' row_first' : '';
        $rowLast = $i === $intRowsTotal - 1 ? ' row_last' : '';
        $evenOrOdd = $i % 2 ? ' odd' : ' even';

        return sprintf('row_%s%s%s%s', $i, $rowFirst, $rowLast, $evenOrOdd);
    }
}
