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
use Contao\CoreBundle\OptIn\OptInTokenAlreadyConfirmedException;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\CoreBundle\OptIn\OptInTokenNoLongerValidException;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\OptInModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingOptInController;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Markocupic\CalendarEventBookingBundle\Event\BookingConfirmEvent;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingOptInException;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\OptIn\OptInTokenFactory;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Markocupic\ContaoFlashMessage\FlashMessage\Message;
use Markocupic\ContaoFlashMessage\FlashMessage\MessageInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class EventBookingOptInControllerTest extends ContaoTestCase
{
    private TranslatorInterface&MockObject $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => $id)
        ;
    }

    public function testLoadBookingReturnsNullWhenRelationEmpty(): void
    {
        $optInModel = $this->mockClassWithProperties(OptInModel::class);
        $optInModel
            ->method('getRelatedRecords')
            ->willReturn([])
        ;

        $controller = $this->createController();
        $this->withFramework($controller, $this->mockContaoFramework());

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'loadBookingFromOptInModel');

        $this->assertNull($method->invoke($controller, $optInModel));
    }

    public function testLoadBookingReturnsModelFromRelation(): void
    {
        $booking = $this->mockBooking();

        $optInModel = $this->mockClassWithProperties(OptInModel::class);
        $optInModel
            ->method('getRelatedRecords')
            ->willReturn([CalendarEventsMemberModel::getTable() => [42]])
        ;

        $adapter = $this->mockAdapter(['findById']);
        $adapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($booking)
        ;

        $controller = $this->createController();
        $this->withFramework($controller, $this->mockContaoFramework([CalendarEventsMemberModel::class => $adapter]));

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'loadBookingFromOptInModel');

        $this->assertSame($booking, $method->invoke($controller, $optInModel));
    }

    public function testGetResponseReturnsNoContentWithoutOptInAction(): void
    {
        $controller = $this->createController();

        $request = new Request();
        $request->query->set('action', 'some-other-action');
        $request->query->set('token', 'cebb-1');

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'getResponse');
        $response = $method->invoke($controller, $this->createTemplate(), $this->mockModule(), $request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testGetResponseRendersMessageForUnknownToken(): void
    {
        // An unknown, tampered or already purged token must render a friendly message
        // instead of bubbling an (uncaught) exception up to the error page.
        $adapter = $this->mockAdapter(['findOneByToken']);
        $adapter
            ->expects($this->once())
            ->method('findOneByToken')
            ->with('invalid-token')
            ->willReturn(null)
        ;

        $message = $this->createMock(Message::class);
        $message
            ->expects($this->once())
            ->method('addError')
            ->with('mod_opt_in.error.confirm_no_more_possible')
        ;

        $message
            ->method('hasMessages')
            ->willReturn(true)
        ;

        $message
            ->method('getAll')
            ->willReturn(['mod_opt_in.error.confirm_no_more_possible'])
        ;

        $message
            ->method('renderUnwrapped')
            ->willReturn('<p>error</p>')
        ;

        $controller = $this->createController(message: $message);
        $this->withFramework($controller, $this->mockContaoFramework([OptInModel::class => $adapter]));

        $template = $this->createTemplate();

        $request = new Request();
        $request->query->set('action', EventBookingOptInController::ACTION);
        $request->query->set('token', 'invalid-token');

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'getResponse');
        $response = $method->invoke($controller, $template, $this->mockModule(), $request);

        // No exception thrown, a real response is returned and the error class is applied.
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('confirm-no-more-possible', $template->get('element_css_classes'));
    }

    public function testValidateRelatedEntitiesThrowsWhenBookingMissing(): void
    {
        $this->assertValidateRelatedEntitiesThrows(null, null, null, 'mod_opt_in.error.booking_not_found', 'booking-not-found');
    }

    public function testValidateRelatedEntitiesThrowsWhenEventMissing(): void
    {
        $this->assertValidateRelatedEntitiesThrows($this->mockBooking(), null, null, 'mod_opt_in.error.corresponding_event_not_found', 'event-not-found');
    }

    public function testValidateRelatedEntitiesThrowsWhenCalendarMissing(): void
    {
        $this->assertValidateRelatedEntitiesThrows($this->mockBooking(), $this->mockEvent(), null, 'mod_opt_in.error.corresponding_calendar_not_found', 'calendar-not-found');
    }

    #[DataProvider('invalidBookingStateProvider')]
    public function testValidateBookingStateThrows(array $booking, array $calendar, array $event, string $expectedKey, SeverityLevel $expectedSeverity): void
    {
        $template = $this->createTemplate();
        $method = new \ReflectionMethod(EventBookingOptInController::class, 'validateBookingState');

        try {
            $method->invoke(
                $this->createController(),
                $template,
                $this->mockCalendar($calendar),
                $this->mockEvent($event),
                $this->mockBooking($booking),
            );
            $this->fail('Expected EventBookingOptInException was not thrown.');
        } catch (EventBookingOptInException $e) {
            $this->assertSame($expectedKey, $e->getMessageKey());
            $this->assertSame($expectedSeverity->value, $e->getSeverityLevel());
        }
    }

    public static function invalidBookingStateProvider(): iterable
    {
        yield 'canceled booking' => [['canceled' => true], [], [], 'mod_opt_in.error.booking_canceled', SeverityLevel::ERROR];
        yield 'already confirmed' => [['optIn' => true], [], [], 'mod_opt_in.info.already_confirmed', SeverityLevel::INFO];
        yield 'opt-in not required' => [[], ['requireOptIn' => false], [], 'mod_opt_in.info.opt_in_not_required', SeverityLevel::INFO];
        yield 'expired booking' => [['expired' => true], [], [], 'mod_opt_in.error.confirm_expired', SeverityLevel::ERROR];
        yield 'event already started' => [[], [], ['startDate' => strtotime('-1 day')], 'mod_opt_in.error.confirm_no_more_possible', SeverityLevel::ERROR];
        yield 'booking end passed' => [[], [], ['bookingEndDate' => strtotime('-1 day')], 'mod_opt_in.error.confirm_no_more_possible', SeverityLevel::ERROR];
    }

    public function testValidateBookingStatePassesForValidBooking(): void
    {
        $method = new \ReflectionMethod(EventBookingOptInController::class, 'validateBookingState');

        $method->invoke(
            $this->createController(),
            $this->createTemplate(),
            $this->mockCalendar(),
            $this->mockEvent(),
            $this->mockBooking(),
        );

        // Reaching this point without an exception is the assertion.
        $this->addToAssertionCount(1);
    }

    public function testProcessConfirmMarksBookingAsConfirmed(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(BookingConfirmEvent::class))
            ->willReturnArgument(0)
        ;

        $message = $this->createMock(Message::class);
        $message
            ->expects($this->once())
            ->method('addInfo')
            ->with('mod_opt_in.info.opt_in_success')
        ;

        $controller = $this->createController(eventDispatcher: $dispatcher, message: $message);

        $booking = $this->mockBooking();
        $booking
            ->expects($this->once())
            ->method('save')
        ;

        $template = $this->createTemplate();

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'processConfirm');
        $method->invoke($controller, $template, $this->mockEvent(), $booking, new Request());

        $this->assertTrue($booking->optIn);
        $this->assertFalse($booking->temporaryReserved);
        $this->assertTrue($template->get('optInSuccess'));
    }

    public function testSendOptInSuccessNotificationSendsWhenConfigured(): void
    {
        $booking = $this->mockBooking();

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService
            ->method('getNotificationTokens')
            ->with($booking)
            ->willReturn(['token' => 'value'])
        ;

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter
            ->expects($this->once())
            ->method('sendNotification')
            ->with(7, ['token' => 'value'])
        ;

        $controller = $this->createController(
            notificationCenter: $notificationCenter,
            notificationService: $notificationService,
        );

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'sendOptInSuccessNotification');
        $method->invoke($controller, $this->mockCalendar(['optInSuccessNotification' => 7]), $booking);
    }

    public function testSendOptInSuccessNotificationSkipsWhenNotConfigured(): void
    {
        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter
            ->expects($this->never())
            ->method('sendNotification')
        ;

        $controller = $this->createController(notificationCenter: $notificationCenter);

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'sendOptInSuccessNotification');
        $method->invoke($controller, $this->mockCalendar(['optInSuccessNotification' => 0]), $this->mockBooking());
    }

    public function testProcessOptInConfirmationConfirmsValidBooking(): void
    {
        $optInModel = $this->mockClassWithProperties(OptInModel::class, ['token' => 'cebb-1']);

        $token = $this->createMock(OptInTokenInterface::class);
        $token
            ->expects($this->once())
            ->method('confirm')
        ;

        $factory = $this->createMock(OptInTokenFactory::class);
        $factory
            ->method('create')
            ->with($optInModel)
            ->willReturn($token)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('beginTransaction')
        ;

        $connection
            ->expects($this->once())
            ->method('commit')
        ;

        $connection
            ->expects($this->never())
            ->method('rollBack')
        ;

        $controller = $this->createController(
            connection: $connection,
            eventDispatcher: $this->dispatcherReturningEvent(),
            lockFactory: $this->lockFactoryWithLock(),
            optInTokenFactory: $factory,
        );

        $booking = $this->mockBooking();
        $booking
            ->expects($this->once())
            ->method('save')
        ;

        $calEvent = $this->mockEvent();
        $calEvent
            ->method('getRelated')
            ->with('pid')
            ->willReturn($this->mockCalendar())
        ;

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'processOptInConfirmation');
        $method->invoke($controller, $this->createTemplate(), $optInModel, 'cebb-1', new Request(), $booking, $calEvent);

        $this->assertTrue($booking->optIn);
        $this->assertFalse($booking->temporaryReserved);
    }

    public function testProcessOptInConfirmationHandlesAlreadyConfirmedToken(): void
    {
        $this->assertConfirmExceptionIsHandled(
            new OptInTokenAlreadyConfirmedException(),
            'mod_opt_in.info.already_confirmed',
        );
    }

    public function testProcessOptInConfirmationHandlesNoLongerValidToken(): void
    {
        $this->assertConfirmExceptionIsHandled(
            new OptInTokenNoLongerValidException(),
            'mod_opt_in.error.token_no_longer_valid',
        );
    }

    public function testAddCssClassToTemplateAppendsClasses(): void
    {
        $template = $this->createTemplate();
        $template->set('element_css_classes', 'foo');

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'addCssClassToTemplate');
        $method->invoke($this->createController(), 'bar baz', $template);

        $this->assertSame('foo bar baz', $template->get('element_css_classes'));
    }

    private function assertValidateRelatedEntitiesThrows(CalendarEventsMemberModel|null $booking, CalendarEventsModel|null $event, CalendarModel|null $calendar, string $expectedKey, string $expectedCssFragment): void
    {
        $template = $this->createTemplate();
        $method = new \ReflectionMethod(EventBookingOptInController::class, 'validateRelatedEntities');

        try {
            $method->invoke($this->createController(), $template, $booking, $event, $calendar);
            $this->fail('Expected EventBookingOptInException was not thrown.');
        } catch (EventBookingOptInException $e) {
            $this->assertSame($expectedKey, $e->getMessageKey());
            $this->assertSame(SeverityLevel::ERROR->value, $e->getSeverityLevel());
        }

        $this->assertStringContainsString($expectedCssFragment, $template->get('element_css_classes'));
    }

    private function assertConfirmExceptionIsHandled(\Throwable $thrownByConfirm, string $expectedMessageKey): void
    {
        $optInModel = $this->mockClassWithProperties(OptInModel::class, ['token' => 'cebb-1']);

        $token = $this->createMock(OptInTokenInterface::class);
        $token
            ->method('confirm')
            ->willThrowException($thrownByConfirm)
        ;

        $factory = $this->createMock(OptInTokenFactory::class);
        $factory
            ->method('create')
            ->willReturn($token)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('beginTransaction')
        ;

        $connection
            ->method('isTransactionActive')
            ->willReturn(true)
        ;

        $connection
            ->expects($this->once())
            ->method('rollBack')
        ;

        $connection
            ->expects($this->never())
            ->method('commit')
        ;

        $message = $this->createMock(Message::class);
        $message
            ->expects($this->once())
            ->method('addInfo')
            ->with($expectedMessageKey)
        ;

        $controller = $this->createController(
            connection: $connection,
            lockFactory: $this->lockFactoryWithLock(),
            message: $message,
            optInTokenFactory: $factory,
        );

        $calEvent = $this->mockEvent();
        $calEvent
            ->method('getRelated')
            ->with('pid')
            ->willReturn($this->mockCalendar())
        ;

        $method = new \ReflectionMethod(EventBookingOptInController::class, 'processOptInConfirmation');
        $method->invoke($controller, $this->createTemplate(), $optInModel, 'cebb-1', new Request(), $this->mockBooking(), $calEvent);
    }

    private function dispatcherReturningEvent(): EventDispatcherInterface&MockObject
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->method('dispatch')
            ->willReturnArgument(0)
        ;

        return $dispatcher;
    }

    private function lockFactoryWithLock(): LockFactory&MockObject
    {
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory
            ->method('createLock')
            ->willReturn($this->createMock(SharedLockInterface::class))
        ;

        return $lockFactory;
    }

    private function createController(Connection|null $connection = null, EventDispatcherInterface|null $eventDispatcher = null, LockFactory|null $lockFactory = null, MessageInterface|null $message = null, NotificationCenter|null $notificationCenter = null, NotificationService|null $notificationService = null, OptInTokenFactory|null $optInTokenFactory = null): EventBookingOptInController
    {
        return new EventBookingOptInController(
            $connection ?? $this->createMock(Connection::class),
            $eventDispatcher ?? $this->createMock(EventDispatcherInterface::class),
            $this->createMock(FigureUtil::class),
            $lockFactory ?? $this->createMock(LockFactory::class),
            $message ?? $this->createMock(Message::class),
            $notificationCenter ?? $this->createMock(NotificationCenter::class),
            $notificationService ?? $this->createMock(NotificationService::class),
            $this->translator,
            $optInTokenFactory ?? $this->createMock(OptInTokenFactory::class),
            null,
            null,
        );
    }

    private function withFramework(EventBookingOptInController $controller, object $framework): void
    {
        $container = new Container();
        $container->set('contao.framework', $framework);
        $controller->setContainer($container);
    }

    private function createTemplate(): FragmentTemplate
    {
        $template = new FragmentTemplate(
            'opt_in',
            static fn (FragmentTemplate $t, Response|null $response = null): Response => $response ?? new Response(),
        );
        $template->setData(['element_css_classes' => '']);

        return $template;
    }

    private function mockModule(array $overrides = []): ModuleModel&MockObject
    {
        return $this->mockClassWithProperties(ModuleModel::class, array_merge(
            ['ceb_addImage' => false],
            $overrides,
        ));
    }

    private function mockBooking(array $overrides = []): CalendarEventsMemberModel&MockObject
    {
        return $this->mockClassWithProperties(CalendarEventsMemberModel::class, array_merge(
            ['id' => 5, 'bookingToken' => 'tok', 'canceled' => false, 'optIn' => false, 'expired' => false, 'temporaryReserved' => true],
            $overrides,
        ));
    }

    private function mockEvent(array $overrides = []): CalendarEventsModel&MockObject
    {
        return $this->mockClassWithProperties(CalendarEventsModel::class, array_merge(
            ['title' => 'My Event', 'startDate' => strtotime('+10 days'), 'bookingEndDate' => 0],
            $overrides,
        ));
    }

    private function mockCalendar(array $overrides = []): CalendarModel&MockObject
    {
        return $this->mockClassWithProperties(CalendarModel::class, array_merge(
            ['requireOptIn' => true, 'optInSuccessNotification' => 0],
            $overrides,
        ));
    }
}
