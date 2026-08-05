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

use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingMyBookingsController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;

class EventBookingMyBookingsControllerTest extends ContaoTestCase
{
    /**
     * The sort direction from the module config must be reduced to ASC/DESC (M-1).
     */
    #[DataProvider('sortingProvider')]
    public function testSortDirectionIsWhitelisted(string $configured, string $expected): void
    {
        $calls = [];
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createQueryBuilder')
            ->willReturn($this->recordingQueryBuilder($calls))
        ;

        $controller = $this->createController($connection);

        $user = $this->mockClassWithProperties(FrontendUser::class, ['id' => 7]);
        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'ceb_modMyBookings_sorting' => $configured,
            'ceb_modMyBookings_startTimeFilter' => '',
            'ceb_addImage' => false,
        ]);

        $method = new \ReflectionMethod(EventBookingMyBookingsController::class, 'getRelatedSubscriptions');
        $result = $method->invoke($controller, $user, $model);

        $this->assertSame([], $result);
        $this->assertSame([['ce.startDate', $expected]], $calls['orderBy'] ?? []);
    }

    public static function sortingProvider(): iterable
    {
        yield 'asc stays asc' => ['asc', 'ASC'];
        yield 'desc stays desc' => ['desc', 'DESC'];
        yield 'mixed case is normalized' => ['DeSc', 'DESC'];
        yield 'injection falls back to asc' => ['startDate; DROP TABLE tl_member', 'ASC'];
        yield 'empty falls back to asc' => ['', 'ASC'];
    }

    /**
     * The "past" time filter must be applied via a bound parameter, not string
     * concatenation (M-3 hardening).
     */
    public function testStartTimeFilterUsesBoundParameter(): void
    {
        $calls = [];
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createQueryBuilder')
            ->willReturn($this->recordingQueryBuilder($calls))
        ;

        $controller = $this->createController($connection);

        $user = $this->mockClassWithProperties(FrontendUser::class, ['id' => 7]);
        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'ceb_modMyBookings_sorting' => 'asc',
            'ceb_modMyBookings_startTimeFilter' => 'past',
            'ceb_addImage' => false,
        ]);

        $method = new \ReflectionMethod(EventBookingMyBookingsController::class, 'getRelatedSubscriptions');
        $method->invoke($controller, $user, $model);

        $andWhereConditions = array_map(static fn (array $args): mixed => $args[0], $calls['andWhere'] ?? []);
        $this->assertContains('ce.startDate < :pastLimit', $andWhereConditions);

        $parameterNames = array_map(static fn (array $args): mixed => $args[0], $calls['setParameter'] ?? []);
        $this->assertContains('pastLimit', $parameterNames);
    }

    /**
     * A booking whose model can no longer be loaded must be skipped instead of
     * triggering a fatal error on a null relation (M-3).
     */
    public function testBookingsWithMissingModelAreSkipped(): void
    {
        $calls = [];
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createQueryBuilder')
            ->willReturn($this->recordingQueryBuilder($calls, [['id' => 99]]))
        ;

        $memberAdapter = $this->createAdapterMock(['findById']);
        $memberAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([CalendarEventsMemberModel::class => $memberAdapter]);

        $controller = $this->createController($connection);
        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $controller->setContainer($container);

        $user = $this->mockClassWithProperties(FrontendUser::class, ['id' => 7]);
        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'ceb_modMyBookings_sorting' => 'asc',
            'ceb_modMyBookings_startTimeFilter' => '',
            'ceb_addImage' => false,
        ]);

        $method = new \ReflectionMethod(EventBookingMyBookingsController::class, 'getRelatedSubscriptions');

        $this->assertSame([], $method->invoke($controller, $user, $model));
    }

    private function createController(Connection $connection): EventBookingMyBookingsController
    {
        return new EventBookingMyBookingsController(
            $this->createMock(Security::class),
            $connection,
            $this->createMock(FigureUtil::class),
        );
    }

    /**
     * @param array<string, list<array<int, mixed>>> $calls
     * @param list<array<string, mixed>>             $result
     */
    private function recordingQueryBuilder(array &$calls, array $result = []): QueryBuilder&MockObject
    {
        $qb = $this->createMock(QueryBuilder::class);

        $record = static function (string $name) use (&$calls, &$qb): \Closure {
            return static function (...$args) use ($name, &$calls, &$qb): QueryBuilder {
                $calls[$name][] = $args;

                return $qb;
            };
        };

        foreach (['select', 'from', 'join', 'where', 'andWhere', 'setParameter', 'orderBy'] as $fluentMethod) {
            $qb
                ->method($fluentMethod)
                ->willReturnCallback($record($fluentMethod))
            ;
        }

        $qb
            ->method('fetchAllAssociative')
            ->willReturn($result)
        ;

        return $qb;
    }
}
