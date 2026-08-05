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

use Codefog\HasteBundle\UrlParser;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingUnsubscribeController;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Markocupic\CalendarEventBookingBundle\Domain\Unsubscribe\UnsubscribeValidator;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Util\FigureUtil;
use Markocupic\ContaoFlashMessage\FlashMessage\Message;
use Markocupic\ContaoFlashMessage\FlashMessage\MessageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class EventBookingUnsubscribeControllerTest extends ContaoTestCase
{
    public function testInvokeReturnsNoContentWhenActionMissing(): void
    {
        $controller = $this->createController(scopeMatcher: $this->scopeMatcher(false));

        $page = $this->mockClassWithProperties(PageModel::class, ['noSearch' => 0]);

        $response = $controller(
            new Request(),
            $this->mockClassWithProperties(ModuleModel::class),
            'main',
            null,
            $page,
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame(1, $page->noSearch);
    }

    public function testInvokeReturnsNoContentWhenBookingTokenEmpty(): void
    {
        $controller = $this->createController(scopeMatcher: $this->scopeMatcher(false));

        $response = $controller(
            new Request(['action' => EventBookingUnsubscribeController::ACTION]),
            $this->mockClassWithProperties(ModuleModel::class),
            'main',
            null,
            null,
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testAddCssClassToTemplateAppendsClasses(): void
    {
        $template = $this->createTemplate();
        $template->set('element_css_classes', 'foo');

        $method = new \ReflectionMethod(EventBookingUnsubscribeController::class, 'addCssClassToTemplate');
        $method->invoke($this->createController(), 'bar baz', $template);

        $this->assertSame('foo bar baz', $template->get('element_css_classes'));
    }

    public function testHandleFormSubmissionCommitsAndRedirectsOnSuccess(): void
    {
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

        $urlParser = $this->createMock(UrlParser::class);
        $urlParser
            ->method('addQueryString')
            ->with('hasUnsubscribed=true')
            ->willReturn('/event?hasUnsubscribed=true')
        ;

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnArgument(0)
        ;

        $booking = $this->mockBooking();
        $booking
            ->expects($this->once())
            ->method('save')
        ;

        $controller = $this->createController(
            connection: $connection,
            eventDispatcher: $dispatcher,
            lockFactory: $this->lockFactoryWithLock(),
            urlParser: $urlParser,
        );
        $this->withFramework($controller, $this->frameworkWithBooking($booking));

        $calEvent = $this->mockEvent();
        $calEvent
            ->method('getRelated')
            ->with('pid')
            ->willReturn($this->mockCalendar(['unsubscribeNotification' => 0]))
        ;

        $method = new \ReflectionMethod(EventBookingUnsubscribeController::class, 'handleFormSubmission');
        $response = $method->invoke($controller, $booking, $calEvent, new Request(), 'tok');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($booking->canceled);
        $this->assertFalse($booking->temporaryReserved);
    }

    public function testHandleFormSubmissionRollsBackAndReturnsNullWhenNotificationThrows(): void
    {
        // Regression: the notification is sent inside the transaction and before the
        // commit. If it throws, the transaction is still active and must be rolled
        // back exactly once - never committed - and a friendly error is shown instead
        // of the exception bubbling up to an error page.
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

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter
            ->method('sendNotification')
            ->willThrowException(new \RuntimeException('SMTP down'))
        ;

        $message = $this->createMock(Message::class);
        $message
            ->expects($this->once())
            ->method('addError')
        ;

        $booking = $this->mockBooking();

        $controller = $this->createController(
            connection: $connection,
            lockFactory: $this->lockFactoryWithLock(),
            message: $message,
            notificationCenter: $notificationCenter,
        );
        $this->withFramework($controller, $this->frameworkWithBooking($booking));

        $calEvent = $this->mockEvent();
        $calEvent
            ->method('getRelated')
            ->with('pid')
            ->willReturn($this->mockCalendar(['unsubscribeNotification' => 5]))
        ;

        $method = new \ReflectionMethod(EventBookingUnsubscribeController::class, 'handleFormSubmission');
        $result = $method->invoke($controller, $booking, $calEvent, new Request(), 'tok');

        $this->assertNull($result);
    }

    public function testHandleFormSubmissionIsIdempotentWhenBookingAlreadyCancelled(): void
    {
        // Regression: a concurrent double submit of the same token must not cancel,
        // dispatch or notify twice. The booking is reloaded under the lock; if it is
        // already cancelled, the method short-circuits to the success redirect without
        // opening a transaction, saving, dispatching or sending a notification.
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('beginTransaction')
        ;

        $connection
            ->expects($this->never())
            ->method('commit')
        ;

        $connection
            ->expects($this->never())
            ->method('rollBack')
        ;

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter
            ->expects($this->never())
            ->method('sendNotification')
        ;

        $urlParser = $this->createMock(UrlParser::class);
        $urlParser
            ->method('addQueryString')
            ->with('hasUnsubscribed=true')
            ->willReturn('/event?hasUnsubscribed=true')
        ;

        // The booking that has already been cancelled by the parallel request.
        $alreadyCancelled = $this->mockBooking(['canceled' => true]);
        $alreadyCancelled
            ->expects($this->never())
            ->method('save')
        ;

        $controller = $this->createController(
            connection: $connection,
            eventDispatcher: $dispatcher,
            lockFactory: $this->lockFactoryWithLock(),
            notificationCenter: $notificationCenter,
            urlParser: $urlParser,
        );
        $this->withFramework($controller, $this->frameworkWithBooking($alreadyCancelled));

        // The stale booking passed in still looks un-cancelled - the fresh read wins.
        $staleBooking = $this->mockBooking(['canceled' => false]);

        $method = new \ReflectionMethod(EventBookingUnsubscribeController::class, 'handleFormSubmission');
        $response = $method->invoke($controller, $staleBooking, $this->mockEvent(), new Request(), 'tok');

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    private function scopeMatcher(bool $isBackend): ScopeMatcher&MockObject
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn($isBackend)
        ;

        return $scopeMatcher;
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

    private function frameworkWithBooking(CalendarEventsMemberModel|null $booking): object
    {
        $adapter = $this->createAdapterMock(['findOneByBookingToken']);
        $adapter
            ->method('findOneByBookingToken')
            ->willReturn($booking)
        ;

        return $this->mockContaoFramework([CalendarEventsMemberModel::class => $adapter]);
    }

    private function withFramework(EventBookingUnsubscribeController $controller, object $framework): void
    {
        $container = new Container();
        $container->set('contao.framework', $framework);
        $controller->setContainer($container);
    }

    private function createTemplate(): FragmentTemplate
    {
        $template = new FragmentTemplate(
            'unsubscribe',
            static fn (FragmentTemplate $t, Response|null $response = null): Response => $response ?? new Response(),
        );
        $template->setData(['element_css_classes' => '']);

        return $template;
    }

    private function mockBooking(array $overrides = []): CalendarEventsMemberModel&MockObject
    {
        return $this->mockClassWithProperties(CalendarEventsMemberModel::class, array_merge(
            ['id' => 5, 'bookingToken' => 'tok', 'canceled' => false, 'temporaryReserved' => true],
            $overrides,
        ));
    }

    private function mockEvent(array $overrides = []): CalendarEventsModel&MockObject
    {
        return $this->mockClassWithProperties(CalendarEventsModel::class, array_merge(
            ['id' => 1, 'title' => 'My Event'],
            $overrides,
        ));
    }

    private function mockCalendar(array $overrides = []): CalendarModel&MockObject
    {
        return $this->mockClassWithProperties(CalendarModel::class, array_merge(
            ['unsubscribeNotification' => 0],
            $overrides,
        ));
    }

    private function createController(Connection|null $connection = null, EventDispatcherInterface|null $eventDispatcher = null, LockFactory|null $lockFactory = null, MessageInterface|null $message = null, NotificationCenter|null $notificationCenter = null, NotificationService|null $notificationService = null, ScopeMatcher|null $scopeMatcher = null, UrlParser|null $urlParser = null): EventBookingUnsubscribeController
    {
        return new EventBookingUnsubscribeController(
            $connection ?? $this->createMock(Connection::class),
            $this->createMock(ContaoCsrfTokenManager::class),
            $eventDispatcher ?? $this->createMock(EventDispatcherInterface::class),
            $this->createMock(FigureUtil::class),
            $lockFactory ?? $this->createMock(LockFactory::class),
            $message ?? $this->createMock(MessageInterface::class),
            $notificationCenter ?? $this->createMock(NotificationCenter::class),
            $notificationService ?? $this->createMock(NotificationService::class),
            $scopeMatcher ?? $this->createMock(ScopeMatcher::class),
            $this->createMock(TranslatorInterface::class),
            $urlParser ?? $this->createMock(UrlParser::class),
            new UnsubscribeValidator($this->createMock(TranslatorInterface::class)),
            null,
            null,
        );
    }
}
