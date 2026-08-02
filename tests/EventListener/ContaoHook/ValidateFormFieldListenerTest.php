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

namespace Markocupic\CalendarEventBookingBundle\Tests\EventListener\ContaoHook;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Form;
use Contao\TestCase\ContaoTestCase;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\EventBookingFormController;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHook\ValidateFormFieldListener;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidateFormFieldListenerTest extends ContaoTestCase
{
    public function testValidateEmailSkipsWhenNotABookingForm(): void
    {
        $widget = $this->mockWidget('email', 'a@example.com');
        $widget
            ->expects($this->never())
            ->method('addError')
        ;

        $listener = $this->listener();

        $listener->validateEmail($widget, 'form', [], $this->mockForm(false));
    }

    public function testValidateEmailSkipsForOtherWidgets(): void
    {
        $widget = $this->mockWidget('firstname', 'John');
        $widget
            ->expects($this->never())
            ->method('addError')
        ;

        $listener = $this->listener();

        $listener->validateEmail($widget, 'form', [], $this->mockForm(true));
    }

    public function testValidateEmailAddsErrorForDuplicate(): void
    {
        $widget = $this->mockWidget('email', 'John@Example.COM');
        $widget
            ->expects($this->once())
            ->method('addError')
        ;

        $controller = $this->mockController(
            calendar: $this->mockClassWithProperties(CalendarModel::class, ['emailUnique' => false]),
            event: $this->mockClassWithProperties(CalendarEventsModel::class, ['id' => 1]),
        );

        $listener = $this->listener($controller, duplicateCount: 1);

        $listener->validateEmail($widget, 'form', [], $this->mockForm(true));

        // Value is normalised to lower case before the lookup.
        $this->assertSame('john@example.com', $widget->value);
    }

    public function testValidateEmailSkipsLookupWhenCalendarAllowsDuplicates(): void
    {
        $widget = $this->mockWidget('email', 'a@example.com');
        $widget
            ->expects($this->never())
            ->method('addError')
        ;

        $controller = $this->mockController(
            calendar: $this->mockClassWithProperties(CalendarModel::class, ['emailUnique' => true]),
            event: $this->mockClassWithProperties(CalendarEventsModel::class, ['id' => 1]),
        );

        $listener = $this->listener($controller, duplicateCount: 5);

        $listener->validateEmail($widget, 'form', [], $this->mockForm(true));
    }

    public function testValidateEscortsRejectsNonNaturalValue(): void
    {
        $widget = $this->mockWidget('escorts', 'abc');
        $widget
            ->expects($this->once())
            ->method('addError')
        ;

        $controller = $this->mockController(
            calendar: $this->mockClassWithProperties(CalendarModel::class, []),
            event: $this->mockClassWithProperties(CalendarEventsModel::class, ['maxEscortsPerBooking' => 3]),
        );

        $listener = $this->listener($controller);

        $listener->validateEscorts($widget, 'form', [], $this->mockForm(true));
    }

    public function testValidateEscortsRejectsWhenAboveMaximum(): void
    {
        $widget = $this->mockWidget('escorts', 5);
        $widget
            ->expects($this->once())
            ->method('addError')
        ;

        $controller = $this->mockController(
            calendar: $this->mockClassWithProperties(CalendarModel::class, []),
            event: $this->mockClassWithProperties(CalendarEventsModel::class, ['maxEscortsPerBooking' => 2]),
        );

        $listener = $this->listener($controller);

        $listener->validateEscorts($widget, 'form', [], $this->mockForm(true));
    }

    public function testValidateEscortsAcceptsValueWithinMaximum(): void
    {
        $widget = $this->mockWidget('escorts', 1);
        $widget
            ->expects($this->never())
            ->method('addError')
        ;

        $controller = $this->mockController(
            calendar: $this->mockClassWithProperties(CalendarModel::class, []),
            event: $this->mockClassWithProperties(CalendarEventsModel::class, ['maxEscortsPerBooking' => 3]),
        );

        $listener = $this->listener($controller);

        $listener->validateEscorts($widget, 'form', [], $this->mockForm(true));
    }

    public function testValidateTicketAmountRejectsWhenAboveMaximum(): void
    {
        $widget = $this->mockWidget('ticketAmount', 5);
        $widget
            ->expects($this->once())
            ->method('addError')
        ;

        $controller = $this->mockController(
            calendar: $this->mockClassWithProperties(CalendarModel::class, []),
            event: $this->mockClassWithProperties(CalendarEventsModel::class, ['maxTicketsPerBooking' => 2]),
        );

        $listener = $this->listener($controller);

        $listener->validateTicketAmount($widget, 'form', [], $this->mockForm(true));
    }

    public function testValidateTicketAmountAcceptsValueWithinMaximum(): void
    {
        $widget = $this->mockWidget('ticketAmount', 1);
        $widget
            ->expects($this->never())
            ->method('addError')
        ;

        $controller = $this->mockController(
            calendar: $this->mockClassWithProperties(CalendarModel::class, []),
            event: $this->mockClassWithProperties(CalendarEventsModel::class, ['maxTicketsPerBooking' => 4]),
        );

        $listener = $this->listener($controller);

        $listener->validateTicketAmount($widget, 'form', [], $this->mockForm(true));
    }

    private function mockWidget(string $name, mixed $value): Widget&MockObject
    {
        return $this->mockClassWithProperties(Widget::class, ['name' => $name, 'value' => $value]);
    }

    private function mockForm(bool $isBookingForm): Form
    {
        return $this->mockClassWithProperties(Form::class, ['isCalendarEventBookingForm' => $isBookingForm]);
    }

    private function mockController(CalendarModel $calendar, CalendarEventsModel $event): EventBookingFormController&MockObject
    {
        $controller = $this->createMock(EventBookingFormController::class);
        $controller
            ->method('getCalendar')
            ->willReturn($calendar)
        ;

        $controller
            ->method('getEvent')
            ->willReturn($event)
        ;

        return $controller;
    }

    private function listener(EventBookingFormController|null $controller = null, int $duplicateCount = 0): ValidateFormFieldListener
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchOne')
            ->willReturn($duplicateCount)
        ;

        $request = new Request();

        if (null !== $controller) {
            $request->attributes->set('_event_booking_form_module', $controller);
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturn('error')
        ;

        return new ValidateFormFieldListener($connection, $requestStack, $translator);
    }
}
