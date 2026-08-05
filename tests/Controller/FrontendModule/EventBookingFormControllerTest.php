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
use Contao\Controller;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\ModuleModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingFormController;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\BookingCapacity;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\EventStatusResolver;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingException;
use Markocupic\CalendarEventBookingBundle\Request\EventUrlResolver;
use Markocupic\CalendarEventBookingBundle\Template\TemplateDataProvider;
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
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventBookingFormControllerTest extends ContaoTestCase
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

    public function testRateLimitIsSkippedWhenDisabled(): void
    {
        $controller = $this->createController(false);

        $this->invokeCheckRateLimit($controller, Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']));

        // No exception means the rate limiter was not enforced.
        $this->addToAssertionCount(1);
    }

    public function testRateLimitIsSkippedWithoutClientIp(): void
    {
        $controller = $this->createController(true, $this->rateLimiterFactory());

        // A request without REMOTE_ADDR has no client IP.
        $this->invokeCheckRateLimit($controller, new Request());

        $this->addToAssertionCount(1);
    }

    public function testRateLimitPassesWhenTokensAvailable(): void
    {
        $controller = $this->createController(true, $this->rateLimiterFactory());

        $this->invokeCheckRateLimit($controller, Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '5.6.7.8']));

        $this->addToAssertionCount(1);
    }

    public function testRateLimitThrowsWhenExhausted(): void
    {
        $factory = $this->rateLimiterFactory(1);

        // Exhaust the single available token for this IP up front.
        $factory->create('9.9.9.9')->consume();

        $controller = $this->createController(true, $factory);

        $this->expectException(EventBookingException::class);
        $this->invokeCheckRateLimit($controller, Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '9.9.9.9']));
    }

    public function testGetResponseThrowsWhenEventMissingInFrontend(): void
    {
        // Regression for the guard: a frontend request without a resolved event must
        // fail fast with a clear LogicException instead of a TypeError deeper down.
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isFrontendRequest')
            ->willReturn(true)
        ;

        // calEvent stays null (no event resolved in __invoke).
        $controller = $this->createController(scopeMatcher: $scopeMatcher);

        $this->expectException(\LogicException::class);

        (new \ReflectionMethod(EventBookingFormController::class, 'getResponse'))
            ->invoke($controller, $this->createTemplate(), $this->mockModule(), new Request())
        ;
    }

    public function testProcessBookingSwallowsUnexpectedErrorAndAddsMessage(): void
    {
        // Regression: an unexpected error during form processing must roll back the
        // transaction, add a flash error and NOT be re-thrown, so getResponse can still
        // render the template with the message.
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('beginTransaction')
        ;

        $connection
            ->expects($this->once())
            ->method('rollBack')
        ;

        $connection
            ->expects($this->never())
            ->method('commit')
        ;

        // Valid booking form so getFormId() (evaluated in the FORM_SUBMIT comparison) succeeds.
        $form = $this->createClassWithPropertiesMock(FormModel::class, ['id' => 3, 'isCalendarEventBookingForm' => true, 'formID' => '']);
        $formAdapter = $this->createAdapterMock(['findById']);
        $formAdapter
            ->method('findById')
            ->willReturn($form)
        ;

        // Contao's form processing throws an unexpected error.
        $controllerAdapter = $this->createAdapterMock(['getForm']);
        $controllerAdapter
            ->method('getForm')
            ->willThrowException(new \RuntimeException('boom'))
        ;

        $message = $this->createMock(Message::class);
        $message
            ->expects($this->once())
            ->method('addError')
        ;

        $controller = $this->createController(
            connection: $connection,
            lockFactory: $this->lockFactoryWithLock(),
            message: $message,
        );
        $this->withFramework($controller, $this->createContaoFrameworkStub([
            FormModel::class => $formAdapter,
            Controller::class => $controllerAdapter,
        ]));

        // No FORM_SUBMIT -> the submission branch is skipped; getForm() still runs and throws.
        (new \ReflectionMethod(EventBookingFormController::class, 'processBooking'))
            ->invoke($controller, $this->createTemplate(), new Request(), 3)
        ;

        // Reaching this point means the exception was swallowed, not re-thrown.
        $this->addToAssertionCount(1);
    }

    public function testGetFormIdThrowsWhenFormMissing(): void
    {
        $controller = $this->createControllerWithForm(null);

        $this->expectException(\Exception::class);
        $this->invokeGetFormId($controller, 7);
    }

    public function testGetFormIdThrowsWhenNotABookingForm(): void
    {
        $form = $this->createClassWithPropertiesMock(FormModel::class, ['id' => 3, 'isCalendarEventBookingForm' => false]);
        $controller = $this->createControllerWithForm($form);

        $this->expectException(\Exception::class);
        $this->invokeGetFormId($controller, 3);
    }

    #[DataProvider('formIdProvider')]
    public function testGetFormIdBuildsExpectedId(array $formProps, string $expected): void
    {
        $form = $this->createClassWithPropertiesMock(FormModel::class, array_merge(['isCalendarEventBookingForm' => true], $formProps));
        $controller = $this->createControllerWithForm($form);

        $this->assertSame($expected, $this->invokeGetFormId($controller, (int) ($formProps['id'] ?? 0)));
    }

    public static function formIdProvider(): iterable
    {
        yield 'uses custom formID' => [['id' => 3, 'formID' => 'contact'], 'auto_contact'];
        yield 'falls back to numeric id' => [['id' => 3, 'formID' => ''], 'auto_form_3'];
    }

    public function testSetFormFieldVisibilityThrowsWhenFieldMissing(): void
    {
        $adapter = $this->createAdapterMock(['findOneBy']);
        $adapter
            ->method('findOneBy')
            ->willReturn(null)
        ;

        $controller = $this->createController(false);
        $this->withFramework($controller, $this->createContaoFrameworkStub([FormFieldModel::class => $adapter]));

        $this->expectException(\Exception::class);
        $this->invokeSetFormFieldVisibility($controller, 5, 'waitingList', true);
    }

    #[DataProvider('visibilityProvider')]
    public function testSetFormFieldVisibilityTogglesInvisibleFlag(bool $show, bool $expectedInvisible): void
    {
        $formField = $this->createClassWithPropertiesMock(FormFieldModel::class, ['invisible' => !$show]);
        $formField
            ->expects($this->once())
            ->method('save')
        ;

        $adapter = $this->createAdapterMock(['findOneBy']);
        $adapter
            ->method('findOneBy')
            ->willReturn($formField)
        ;

        $controller = $this->createController(false);
        $this->withFramework($controller, $this->createContaoFrameworkStub([FormFieldModel::class => $adapter]));

        $this->invokeSetFormFieldVisibility($controller, 5, 'waitingList', $show);

        $this->assertSame($expectedInvisible, $formField->invisible);
    }

    public static function visibilityProvider(): iterable
    {
        yield 'show -> visible' => [true, false];
        yield 'hide -> invisible' => [false, true];
    }

    #[DataProvider('waitingListFallbackProvider')]
    public function testShouldFallBackToWaitingList(bool $canFulfill, bool $canFulfillWaitingList, bool $expected): void
    {
        $bookingCapacity = $this->createMock(BookingCapacity::class);
        $bookingCapacity
            ->method('canFulfillBookingRequest')
            ->willReturn($canFulfill)
        ;

        $bookingCapacity
            ->method('canFulfillBookingRequestWaitingList')
            ->willReturn($canFulfillWaitingList)
        ;

        $controller = $this->createController(false, null, $bookingCapacity);
        $this->setCalEvent($controller);

        $method = new \ReflectionMethod(EventBookingFormController::class, 'shouldFallBackToWaitingList');

        $this->assertSame($expected, $method->invoke($controller, 1));
    }

    public static function waitingListFallbackProvider(): iterable
    {
        yield 'still bookable -> no fallback' => [true, true, false];
        yield 'full but waiting list open -> fallback' => [false, true, true];
        yield 'full and waiting list full -> no fallback' => [false, false, false];
    }

    #[DataProvider('waitingListAvailabilityProvider')]
    public function testIsWaitingListAvailable(bool $fullyBooked, bool $waitingListFull, bool $expected): void
    {
        $bookingCapacity = $this->createMock(BookingCapacity::class);
        $bookingCapacity
            ->method('isFullyBooked')
            ->willReturn($fullyBooked)
        ;

        $bookingCapacity
            ->method('isWaitingListFull')
            ->willReturn($waitingListFull)
        ;

        $controller = $this->createController(false, null, $bookingCapacity);
        $this->setCalEvent($controller);

        $method = new \ReflectionMethod(EventBookingFormController::class, 'isWaitingListAvailable');

        $this->assertSame($expected, $method->invoke($controller));
    }

    public static function waitingListAvailabilityProvider(): iterable
    {
        yield 'not full -> unavailable' => [false, false, false];
        yield 'full, waiting list open -> available' => [true, false, true];
        yield 'full, waiting list full -> unavailable' => [true, true, false];
    }

    private function setCalEvent(EventBookingFormController $controller): void
    {
        $property = new \ReflectionProperty(EventBookingFormController::class, 'calEvent');
        $property->setValue($controller, $this->createClassWithPropertiesMock(CalendarEventsModel::class));
    }

    private function invokeCheckRateLimit(EventBookingFormController $controller, Request $request): void
    {
        (new \ReflectionMethod(EventBookingFormController::class, 'checkRateLimit'))->invoke($controller, $request);
    }

    private function invokeGetFormId(EventBookingFormController $controller, int $formId): string
    {
        return (new \ReflectionMethod(EventBookingFormController::class, 'getFormId'))->invoke($controller, $formId);
    }

    private function invokeSetFormFieldVisibility(EventBookingFormController $controller, int $formId, string $name, bool $show): void
    {
        (new \ReflectionMethod(EventBookingFormController::class, 'setFormFieldVisibility'))->invoke($controller, $formId, $name, $show);
    }

    private function createTemplate(): FragmentTemplate
    {
        $template = new FragmentTemplate(
            'event_booking_form',
            static fn (FragmentTemplate $t, Response|null $response = null): Response => $response ?? new Response(),
        );
        $template->setData(['element_css_classes' => '']);

        return $template;
    }

    private function mockModule(array $overrides = []): ModuleModel&MockObject
    {
        return $this->createClassWithPropertiesMock(ModuleModel::class, array_merge(['form' => 0], $overrides));
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

    private function createControllerWithForm(FormModel|null $form): EventBookingFormController
    {
        $adapter = $this->createAdapterMock(['findById']);
        $adapter
            ->method('findById')
            ->willReturn($form)
        ;

        $controller = $this->createController(false);
        $this->withFramework($controller, $this->createContaoFrameworkStub([FormModel::class => $adapter]));

        return $controller;
    }

    private function createController(bool $rateLimitEnable = false, RateLimiterFactory|null $rateLimiterFactory = null, BookingCapacity|null $bookingCapacity = null, Connection|null $connection = null, LockFactory|null $lockFactory = null, MessageInterface|null $message = null, ScopeMatcher|null $scopeMatcher = null, EventStatusResolver|null $eventStatusResolver = null): EventBookingFormController
    {
        return new EventBookingFormController(
            $this->createMock(TemplateDataProvider::class),
            $connection ?? $this->createMock(Connection::class),
            $this->createMock(EventDispatcherInterface::class),
            $bookingCapacity ?? $this->createMock(BookingCapacity::class),
            $eventStatusResolver ?? $this->createMock(EventStatusResolver::class),
            $this->createMock(EventUrlResolver::class),
            $lockFactory ?? $this->createMock(LockFactory::class),
            $message ?? $this->createMock(MessageInterface::class),
            $rateLimiterFactory ?? $this->rateLimiterFactory(),
            $scopeMatcher ?? $this->createMock(ScopeMatcher::class),
            $this->translator,
            $rateLimitEnable,
            null,
        );
    }

    private function rateLimiterFactory(int $limit = 5): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => 'cebb_test', 'policy' => 'fixed_window', 'limit' => $limit, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
    }

    private function withFramework(EventBookingFormController $controller, object $framework): void
    {
        $container = new Container();
        $container->set('contao.framework', $framework);
        $controller->setContainer($container);
    }
}
