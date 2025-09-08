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

namespace Markocupic\CalendarEventBookingBundle\Tests\Cron;

use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Cron\HandleCanceledBookingsCron;
use Markocupic\CalendarEventBookingBundle\Event\AutoDeleteCanceledBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class HandleCanceledBookingsCronTest extends ContaoTestCase
{
    private Connection $connection;

    private EventDispatcherInterface $eventDispatcher;

    private RequestStack $requestStack;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testProcessSingleCanceledBookingWithNullModel(): void
    {
        $model = null;
        $adapter = $this->mockAdapter(['findById']);
        $adapter
            ->method('findById')
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([CalendarEventsMemberModel::class => $adapter]);

        // Create an instance of HandleCanceledBookingsCron
        $handler = new HandleCanceledBookingsCron(
            $this->connection,
            $framework,
            $this->eventDispatcher,
            $this->requestStack,
            true,
            $this->logger,
        );

        // Execute the method
        $method = new \ReflectionMethod(HandleCanceledBookingsCron::class, 'processSingleCanceledBooking');
        $result = $method->invokeArgs($handler, [1, null]);

        $this->assertFalse($result);
    }

    public function testProcessSingleCanceledBookingWithDeletionAllowed(): void
    {
        $model = $this->mockClassWithProperties(CalendarEventsMemberModel::class, ['id' => 1, 'canceled' => 1]);
        $model
            ->expects($this->once())
            ->method('delete')
            ->willReturn(1)
        ;

        $adapter = $this->mockAdapter(['findById']);
        $adapter
            ->method('findById')
            ->with(1)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([CalendarEventsMemberModel::class => $adapter]);

        $this->eventDispatcher
            ->method('dispatch')
            ->with($this->isInstanceOf(AutoDeleteCanceledBookingEvent::class))
            ->willReturnCallback(
                static function (AutoDeleteCanceledBookingEvent $event) {
                    $event->setShouldDelete(true);

                    return $event;
                },
            )
        ;

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Canceled booking ID 1 has been deleted automatically.'))
        ;

        // Create an instance of HandleCanceledBookingsCron
        $handler = new HandleCanceledBookingsCron(
            $this->connection,
            $framework,
            $this->eventDispatcher,
            $this->requestStack,
            true,
            $this->logger,
        );

        // Execute the method
        $method = new \ReflectionMethod(HandleCanceledBookingsCron::class, 'processSingleCanceledBooking');
        $result = $method->invokeArgs($handler, [1, null]);

        $this->assertTrue($result);
    }

    public function testProcessSingleCanceledBookingWithDeletionNotAllowed(): void
    {
        $model = $this->mockClassWithProperties(CalendarEventsMemberModel::class, ['id' => 1, 'canceled' => 1]);
        $model
            ->expects($this->never())
            ->method('delete')
        ;

        $adapter = $this->mockAdapter(['findById']);
        $adapter
            ->method('findById')
            ->with(1)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([CalendarEventsMemberModel::class => $adapter]);

        $this->eventDispatcher
            ->method('dispatch')
            ->with($this->isInstanceOf(AutoDeleteCanceledBookingEvent::class))
            ->willReturnCallback(
                static function (AutoDeleteCanceledBookingEvent $event) {
                    $event->setShouldDelete(false);

                    return $event;
                },
            )
        ;

        $this->logger
            ->expects($this->never())
            ->method('info')
        ;

        // Create an instance of HandleCanceledBookingsCron
        $handler = new HandleCanceledBookingsCron(
            $this->connection,
            $framework,
            $this->eventDispatcher,
            $this->requestStack,
            true,
            $this->logger,
        );

        // Execute the method
        $method = new \ReflectionMethod(HandleCanceledBookingsCron::class, 'processSingleCanceledBooking');
        $result = $method->invokeArgs($handler, [1, null]);

        $this->assertFalse($result);
    }
}
