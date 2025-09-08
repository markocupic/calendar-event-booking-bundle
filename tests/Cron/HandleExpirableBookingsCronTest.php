<?php

declare(strict_types=1);

namespace Markocupic\CalendarEventBookingBundle\Tests\Cron;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Markocupic\CalendarEventBookingBundle\Cron\HandleExpirableBookingsCron;
use Markocupic\CalendarEventBookingBundle\Event\AutoExpireReservedBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class HandleExpirableBookingsCronTest extends ContaoTestCase
{
    private Connection|MockObject $connection;

    private ContaoFramework|MockObject $framework;

    private EventDispatcherInterface|MockObject $eventDispatcher;

    private MockObject|RequestStack $requestStack;

    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->framework = $this->mockContaoFramework();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testProcessAutoExpireDoesNothingWhenAutoExpireIsDisabled(): void
    {
        $this->framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $cron = new HandleExpirableBookingsCron(
            $this->connection,
            $this->framework,
            $this->eventDispatcher,
            $this->requestStack,
            false,
            3600,
            $this->logger,
        );

        $cron->processAutoExpire();
    }

    public function testProcessAutoExpireDoesNothingWhenTimeLimitIsNegative(): void
    {
        $this->framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $cron = new HandleExpirableBookingsCron(
            $this->connection,
            $this->framework,
            $this->eventDispatcher,
            $this->requestStack,
            true,
            -1,
            $this->logger,
        );

        $cron->processAutoExpire();
    }

    public function testProcessAutoExpireCallsFetchAndProcessesBookings(): void
    {
        $adapter = $this->mockAdapter(['findById']);
        $adapter
            ->expects($this->exactly(3))
            ->method('findById')
            ->willReturnOnConsecutiveCalls($this->mockBookingModel(1), $this->mockBookingModel(2), $this->mockBookingModel(3))
        ;

        $framework = $this->mockContaoFramework([CalendarEventsMemberModel::class => $adapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $this->connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->mockQueryBuilder([1, 2, 3]))
        ;

        $request = $this->createMock(Request::class);
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $this->eventDispatcher
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturnCallback(
                static function (AutoExpireReservedBookingEvent $event) {
                    $event->setShouldExpire(true);

                    return $event;
                },
            )
        ;

        $this->connection
            ->expects($this->exactly(3))
            ->method('update')
            ->with(
                $this->equalTo('tl_calendar_events_member'),
                $this->equalTo(['expired' => 1, 'temporaryReserved' => 0]),
                $this->callback(
                    static function ($criteria) use (&$callCount) {
                        ++$callCount;
                        if (1 === $callCount) {
                            return $criteria === ['id' => 1];
                        }

                        if (2 === $callCount) {
                            return $criteria === ['id' => 2];
                        }

                        return $criteria === ['id' => 3];
                    },
                ),
                $this->equalTo([Types::INTEGER]),
            )
            ->willReturn(1)
        ;

        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->with($this->stringContains('Temporary reserved booking ID'))
        ;

        $cron = new HandleExpirableBookingsCron(
            $this->connection,
            $framework,
            $this->eventDispatcher,
            $this->requestStack,
            true,
            3600,
            $this->logger,
        );

        $cron->processAutoExpire();
    }

    public function testProcessAutoExpireSkipsProcessingWhenBookingDoesNotExpire(): void
    {
        $adapter = $this->mockAdapter(['findById']);
        $adapter
            ->expects($this->exactly(3))
            ->method('findById')
            ->willReturnOnConsecutiveCalls($this->mockBookingModel(1), $this->mockBookingModel(2), $this->mockBookingModel(3))
        ;

        $framework = $this->mockContaoFramework([CalendarEventsMemberModel::class => $adapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $this->connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->mockQueryBuilder([1, 2, 3]))
        ;

        $request = $this->createMock(Request::class);
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $this->eventDispatcher
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturnCallback(
                static function (AutoExpireReservedBookingEvent $event) {
                    $event->setShouldExpire(false);

                    return $event;
                },
            )
        ;

        $this->connection
            ->expects($this->never())
            ->method('update')
        ;

        $this->logger
            ->expects($this->never())
            ->method('info')
        ;

        $cron = new HandleExpirableBookingsCron(
            $this->connection,
            $framework,
            $this->eventDispatcher,
            $this->requestStack,
            true,
            3600,
            $this->logger,
        );

        $cron->processAutoExpire();
    }

    private function mockBookingModel(int $id): CalendarEventsMemberModel&MockObject
    {
        return $this->mockClassWithProperties(CalendarEventsMemberModel::class, [
            'id' => $id,
            'expired' => 1,
            'temporaryReserved' => 0,
        ]);
    }

    private function mockQueryBuilder(array $ids): QueryBuilder&MockObject
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('id')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with('tl_calendar_events_member', 't')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('t.temporaryReserved = 1 AND t.expired = 0 AND t.addedOn != ""')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->with('t.addedOn < :timeCutoff')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->willReturn($ids)
        ;

        return $queryBuilder;
    }
}
