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

namespace Markocupic\CalendarEventBookingBundle\Tests\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingMemberListController;
use Markocupic\CalendarEventBookingBundle\Request\EventUrlResolver;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EventBookingMemberListControllerTest extends ContaoTestCase
{
    #[DataProvider('rowClassProvider')]
    public function testGetRowClass(int $i, int $total, string $expected): void
    {
        $controller = $this->createController($this->createMock(Connection::class));

        $method = new \ReflectionMethod(EventBookingMemberListController::class, 'getRowClass');

        $this->assertSame($expected, $method->invoke($controller, $i, $total));
    }

    public static function rowClassProvider(): iterable
    {
        yield 'first of many' => [0, 3, 'row_0 row_first even'];
        yield 'middle, odd' => [1, 3, 'row_1 odd'];
        yield 'last, even' => [2, 3, 'row_2 row_last even'];
        yield 'single row is first and last' => [0, 1, 'row_0 row_first row_last even'];
    }

    /**
     * A valid order column is kept and the direction is normalized to DESC.
     */
    public function testGetBookingsKeepsWhitelistedOrderColumn(): void
    {
        $calls = [];
        $qb = $this->recordingQueryBuilder($calls);
        $controller = $this->createController($this->mockConnection($qb, ['dateadded' => null]));

        $method = new \ReflectionMethod(EventBookingMemberListController::class, 'getBookings');
        $method->invoke($controller, 5, [], ['dateAdded::desc']);

        $this->assertSame([['t.dateAdded', 'DESC']], $calls['addOrderBy'] ?? []);
    }

    /**
     * An unknown / injected order column is silently skipped (M-1).
     */
    public function testGetBookingsSkipsUnknownOrderColumn(): void
    {
        $calls = [];
        $qb = $this->recordingQueryBuilder($calls);
        $controller = $this->createController($this->mockConnection($qb, ['dateadded' => null]));

        $method = new \ReflectionMethod(EventBookingMemberListController::class, 'getBookings');
        $method->invoke($controller, 5, [], ['id = 1) UNION SELECT * FROM tl_member --::DESC']);

        $this->assertSame([], $calls['addOrderBy'] ?? []);
    }

    /**
     * An invalid sort direction falls back to ASC (M-1).
     */
    public function testGetBookingsNormalizesInvalidDirection(): void
    {
        $calls = [];
        $qb = $this->recordingQueryBuilder($calls);
        $controller = $this->createController($this->mockConnection($qb, ['dateadded' => null]));

        $method = new \ReflectionMethod(EventBookingMemberListController::class, 'getBookings');
        $method->invoke($controller, 5, [], ['dateAdded::; DROP TABLE x']);

        $this->assertSame([['t.dateAdded', 'ASC']], $calls['addOrderBy'] ?? []);
    }

    /**
     * The event scope (t.pid) is always applied, and a single status filter is
     * AND-combined onto it instead of OR-widening the result set (H-1).
     */
    public function testGetBookingsScopesStatusFilterWithAndGroup(): void
    {
        $calls = [];
        $qb = $this->recordingQueryBuilder($calls);
        $controller = $this->createController($this->mockConnection($qb, ['pid' => null, 'bookingstate' => null]));

        $method = new \ReflectionMethod(EventBookingMemberListController::class, 'getBookings');
        $method->invoke($controller, 5, ['bookingState::confirmed'], []);

        $this->assertSame([['t.pid = :pid']], $calls['where'] ?? []);
        $this->assertSame([['(t.bookingState = :bookingState)']], $calls['andWhere'] ?? []);
        $this->assertArrayNotHasKey('orWhere', $calls);
    }

    /**
     * Multiple status conditions are OR-combined inside a single AND group (H-1).
     */
    public function testGetBookingsCombinesMultipleStatusConditionsWithOr(): void
    {
        $calls = [];
        $qb = $this->recordingQueryBuilder($calls);
        $controller = $this->createController($this->mockConnection($qb, ['pid' => null, 'canceled' => null, 'expired' => null]));

        $method = new \ReflectionMethod(EventBookingMemberListController::class, 'getBookings');
        $method->invoke($controller, 5, ['canceled::true', 'expired::true'], []);

        $this->assertSame([['(t.canceled = :canceled OR t.expired = :expired)']], $calls['andWhere'] ?? []);
        $this->assertArrayNotHasKey('orWhere', $calls);
    }

    /**
     * Without a status filter, only the event scope remains and no status group is
     * added (H-1: no more site-wide leak).
     */
    public function testGetBookingsWithoutStatusFilterOnlyScopesByEvent(): void
    {
        $calls = [];
        $qb = $this->recordingQueryBuilder($calls);
        $controller = $this->createController($this->mockConnection($qb, ['pid' => null]));

        $method = new \ReflectionMethod(EventBookingMemberListController::class, 'getBookings');
        $method->invoke($controller, 5, [], []);

        $this->assertSame([['t.pid = :pid']], $calls['where'] ?? []);
        $this->assertArrayNotHasKey('andWhere', $calls);
        $this->assertArrayNotHasKey('orWhere', $calls);
    }

    /**
     * The schema is only inspected once even for repeated column checks (L-3).
     */
    public function testColumnExistsCachesSchemaLookup(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('listTableColumns')
            ->willReturn(['dateadded' => null])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $controller = $this->createController($connection);

        $method = new \ReflectionMethod(EventBookingMemberListController::class, 'columnExists');

        $this->assertTrue($method->invoke($controller, 'tl_calendar_events_member', 'dateAdded'));
        $this->assertTrue($method->invoke($controller, 'tl_calendar_events_member', 'dateadded'));
        $this->assertFalse($method->invoke($controller, 'tl_calendar_events_member', 'doesNotExist'));
    }

    /**
     * A frontend request whose resolved event is not bookable must short-circuit to
     * an empty 204 response instead of rendering the member list.
     */
    #[DataProvider('notBookableProvider')]
    public function testInvokeReturnsNoContentWhenEventIsNotBookable(array $calendarProps, array $eventProps): void
    {
        $calendar = $this->createClassWithPropertiesMock(CalendarModel::class, $calendarProps);
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class, $eventProps);
        $event
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendar)
        ;

        $resolver = $this->createMock(EventUrlResolver::class);
        $resolver
            ->method('resolve')
            ->willReturn($event)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isFrontendRequest')
            ->willReturn(true)
        ;

        $controller = $this->createController(eventUrlResolver: $resolver, scopeMatcher: $scopeMatcher);

        $response = $controller(
            new Request(),
            $this->createClassWithPropertiesMock(ModuleModel::class),
            'main',
            null,
            $this->createClassWithPropertiesMock(PageModel::class),
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public static function notBookableProvider(): iterable
    {
        yield 'event booking disabled on calendar' => [['allowEventBooking' => false], ['enableBookingForm' => true, 'published' => true]];
        yield 'booking form disabled on event' => [['allowEventBooking' => true], ['enableBookingForm' => false, 'published' => true]];
        yield 'event not published' => [['allowEventBooking' => true], ['enableBookingForm' => true, 'published' => false]];
    }

    private function createController(Connection|null $connection = null, EventUrlResolver|null $eventUrlResolver = null, ScopeMatcher|null $scopeMatcher = null): EventBookingMemberListController
    {
        return new EventBookingMemberListController(
            $connection ?? $this->createMock(Connection::class),
            $this->createMock(FigureUtil::class),
            $eventUrlResolver ?? $this->createMock(EventUrlResolver::class),
            $scopeMatcher ?? $this->createMock(ScopeMatcher::class),
        );
    }

    private function mockConnection(QueryBuilder $qb, array $columns): Connection&MockObject
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('listTableColumns')
            ->willReturn($columns)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        return $connection;
    }

    /**
     * Returns a QueryBuilder mock that records every fluent call into $calls
     * (keyed by method name) and returns an empty result set.
     *
     * @param array<string, list<array<int, mixed>>> $calls
     */
    private function recordingQueryBuilder(array &$calls): QueryBuilder&MockObject
    {
        $qb = $this->createMock(QueryBuilder::class);

        $record = static function (string $name) use (&$calls, &$qb): \Closure {
            return static function (...$args) use ($name, &$calls, &$qb): QueryBuilder {
                $calls[$name][] = $args;

                return $qb;
            };
        };

        foreach (['select', 'from', 'setParameter', 'orWhere', 'andWhere', 'where', 'addOrderBy', 'orderBy'] as $fluentMethod) {
            $qb
                ->method($fluentMethod)
                ->willReturnCallback($record($fluentMethod))
            ;
        }

        $qb
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        return $qb;
    }
}
