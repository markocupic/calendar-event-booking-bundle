<?php

declare(strict_types=1);

namespace Markocupic\CalendarEventBookingBundle\Tests\Cron;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Markocupic\CalendarEventBookingBundle\Cron\DeleteExpiredBookingsCron;
use Markocupic\CalendarEventBookingBundle\Event\AutoDeleteExpiredBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteExpiredBookingsCronTest extends ContaoTestCase
{
    private const TABLE_NAME = 'tl_calendar_events_member';

    private Connection|MockObject $connection;

    private EventDispatcherInterface|MockObject $eventDispatcher;

    private MockObject|RequestStack $requestStack;

    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeMocks();
    }

    public function testProcessAutoDeleteDoesNothingWhenDisabled(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $this->connection
            ->expects($this->never())
            ->method('createQueryBuilder')
        ;

        $cron = $this->createCronJobInstance($framework, false);

        $cron();
    }

    public function testAutoDeletionWhenShouldDelete(): void
    {
        $bookingIds = [1, 2, 3];

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
            ->willReturn($this->mockQueryBuilder($bookingIds))
        ;

        $this->connection
            ->expects($this->exactly(3))
            ->method('delete')
            ->with(self::TABLE_NAME, $this->isArray(), $this->isArray())
            ->willReturn(1)
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
                static function (AutoDeleteExpiredBookingEvent $event) {
                    $event->setShouldDelete(true);

                    return $event;
                },
            )
        ;

        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->with($this->stringContains(' has been deleted automatically.'))
        ;

        $cron = $this->createCronJobInstance($framework, true);

        $cron();
    }

    public function testAutoDeletionWhenDeletionHasBeenStoppedByEventListener(): void
    {
        $bookingIds = [1, 2, 3];

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
            ->willReturn($this->mockQueryBuilder($bookingIds))
        ;

        $this->connection
            ->expects($this->never())
            ->method('delete')
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
                static function (AutoDeleteExpiredBookingEvent $event) {
                    $event->setShouldDelete(false);

                    return $event;
                },
            )
        ;

        $this->logger
            ->expects($this->never())
            ->method('info')
        ;

        $cron = $this->createCronJobInstance($framework, true);

        $cron();
    }

    private function initializeMocks(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function mockBookingModel(int $id): CalendarEventsMemberModel&MockObject
    {
        return $this->mockClassWithProperties(CalendarEventsMemberModel::class, ['id' => $id]);
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
            ->with(self::TABLE_NAME, 't')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('t.expired = 1')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->willReturn($ids)
        ;

        return $queryBuilder;
    }

    private function createCronJobInstance(ContaoFramework|MockObject $framework, bool $isEnabled): DeleteExpiredBookingsCron
    {
        return new DeleteExpiredBookingsCron(
            $this->connection,
            $framework,
            $this->eventDispatcher,
            $this->requestStack,
            $isEnabled,
            $this->logger,
        );
    }
}
