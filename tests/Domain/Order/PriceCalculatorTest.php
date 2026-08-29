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

namespace Markocupic\CalendarEventBookingBundle\Tests\Domain\Order;

use Contao\CalendarEventsModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Domain\Order\PriceCalculator;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateGrossAmountPerItemEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateGrossTotalAmountEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateNetAmountPerItemEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateNetTotalAmountEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateVatAmountPerItemEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\CalculateVatTotalAmountEvent;
use Markocupic\CalendarEventBookingBundle\Event\PriceCalculator\GetTaxValueEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PriceCalculatorTest extends ContaoTestCase
{
    public function testCalcGrossAmountPerItem(): void
    {
        $calEvent = $this->calEvent(netPrice: 100.0, taxValue: 20.0);
        $booking = $this->booking();

        $expectedVat = 100.0 * 20.0 / 100.0;
        $expectedGrossAmount = 100.0 + $expectedVat;

        $this->assertSame(
            round($expectedGrossAmount, 2),
            $this->calculator()->calcGrossAmountPerItem($calEvent, $booking),
        );
    }

    /**
     * Test the getCurrencyCode method of the PriceCalculator class.
     *
     * Note: getCurrencyCode() is the one method that takes no booking and fires no
     * event - the currency belongs to the calendar event alone.
     */
    public function testGetCurrencyCode(): void
    {
        $calEvent = $this->calEvent(currencyCode: 'USD');

        $this->assertSame('USD', $this->calculator()->getCurrencyCode($calEvent));
    }

    /**
     * Test the getCurrencyCode method when currency code is null.
     */
    public function testGetCurrencyCodeWhenNull(): void
    {
        $calEvent = $this->calEvent(currencyCode: null);

        $this->assertSame('', $this->calculator()->getCurrencyCode($calEvent));
    }

    public function testCalcGrossTotalAmount(): void
    {
        $calEvent = $this->calEvent(netPrice: 100.0, taxValue: 20.0);
        $booking = $this->booking(3);

        $expectedVat = 100.0 * 20.0 / 100.0;
        $expectedGrossAmount = 100.0 + $expectedVat;
        $expectedGrossTotalAmount = 3 * $expectedGrossAmount;

        $this->assertSame(
            round($expectedGrossTotalAmount, 2),
            $this->calculator()->calcGrossTotalAmount($calEvent, $booking),
        );
    }

    public function testCalcNetAmountPerItem(): void
    {
        $calEvent = $this->calEvent(netPrice: 200.0);
        $booking = $this->booking();

        $this->assertSame(200.0, $this->calculator()->calcNetAmountPerItem($calEvent, $booking));
    }

    /**
     * Contao models return DOUBLE columns (netPrice) as strings. Because the class
     * declares strict_types, the calculator must cast the raw model value to float
     * before handing it to formatPrice(float) - otherwise a TypeError is thrown.
     */
    public function testCalcNetAmountPerItemWithStringNetPrice(): void
    {
        $calEvent = $this->calEvent(netPrice: '150.00');
        $booking = $this->booking();

        $this->assertSame(150.0, $this->calculator()->calcNetAmountPerItem($calEvent, $booking));
    }

    /**
     * Guards the whole per-item chain (net -> vat -> gross) against string model
     * values, mirroring what a real Contao model returns from the database.
     */
    public function testCalcGrossAmountPerItemWithStringModelValues(): void
    {
        $calEvent = $this->calEvent(netPrice: '100.00', taxValue: '20.00');
        $booking = $this->booking();
        $priceCalculator = $this->calculator();

        $this->assertSame(20.0, $priceCalculator->calcVatAmountPerItem($calEvent, $booking));
        $this->assertSame(120.0, $priceCalculator->calcGrossAmountPerItem($calEvent, $booking));
    }

    public function testCalcNetTotalAmount(): void
    {
        $calEvent = $this->calEvent(netPrice: 150.0);
        $priceCalculator = $this->calculator();

        $this->assertSame(150.0, $priceCalculator->calcNetTotalAmount($calEvent, $this->booking(1)));
        $this->assertSame(750.0, $priceCalculator->calcNetTotalAmount($calEvent, $this->booking(5)));
    }

    /**
     * Test the calcVatAmountPerItem method of the PriceCalculator class.
     */
    public function testCalcVatAmountPerItem(): void
    {
        $calEvent = $this->calEvent(netPrice: 100.0, taxValue: 20.0);
        $booking = $this->booking();

        $this->assertSame(20.0, $this->calculator()->calcVatAmountPerItem($calEvent, $booking));
    }

    /**
     * Test the getTaxValue method of the PriceCalculator class.
     */
    public function testGetTaxValue(): void
    {
        $calEvent = $this->calEvent(taxValue: 15.0);
        $booking = $this->booking();

        $this->assertSame(15.0, $this->calculator()->getTaxValue($calEvent, $booking));
    }

    /**
     * Test the getTaxValue method when the taxValue property is null.
     */
    public function testGetTaxValueWhenNull(): void
    {
        $calEvent = $this->calEvent(taxValue: null);
        $booking = $this->booking();

        $this->assertSame(0.0, $this->calculator()->getTaxValue($calEvent, $booking));
    }

    /**
     * Test the calcVatTotalAmount method of the PriceCalculator class.
     */
    public function testCalcVatTotalAmount(): void
    {
        $calEvent = $this->calEvent(netPrice: 120.0, taxValue: 15.0);
        $priceCalculator = $this->calculator();

        $expectedVatPerItem = 120.0 * 15.0 / 100.0;

        $this->assertSame(
            round($expectedVatPerItem * 2, 2),
            $priceCalculator->calcVatTotalAmount($calEvent, $this->booking(2)),
        );

        $this->assertSame(
            round($expectedVatPerItem * 5, 2),
            $priceCalculator->calcVatTotalAmount($calEvent, $this->booking(5)),
        );
    }

    #[DataProvider('formatPrice')]
    public function testFormatPrice(float $test, float $expected): void
    {
        $method = new \ReflectionMethod(PriceCalculator::class, 'formatPrice');
        $result = $method->invokeArgs($this->calculator(), [$test]);

        $this->assertSame($expected, $result);
    }

    public static function formatPrice(): iterable
    {
        yield [0.000000001, 0.0];
        yield [-0.000000001, 0.0];
        yield [123.4567890123, 123.46];
        yield [-123.4567890123, -123.46];
        yield [-123.4547890123, -123.45];
        yield [123.4567890123, 123.46];
        yield [123.4547890123, 123.45];
        yield [0.0, 0.0];
    }

    // -------------------------------------------------------------------------
    // Events
    // -------------------------------------------------------------------------

    /**
     * Every calculating method must hand both the calendar event and the booking
     * to its event, otherwise a listener cannot tell whose price it is looking at.
     *
     * @param class-string $eventClass
     */
    #[DataProvider('dispatchedEventProvider')]
    public function testEventCarriesTheCalendarEventAndTheBooking(string $eventClass, string $method): void
    {
        $calEvent = $this->calEvent(netPrice: 100.0, taxValue: 20.0);
        $booking = $this->booking(2);

        $received = [];

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            $eventClass,
            static function (object $event) use (&$received): void {
                $received[] = $event;
            },
        );

        $this->calculator($dispatcher)->$method($calEvent, $booking);

        $this->assertNotEmpty($received, \sprintf('%s was never dispatched by %s().', $eventClass, $method));

        foreach ($received as $event) {
            $this->assertSame($calEvent, $event->getCalendarEvent());
            $this->assertSame($booking, $event->getBooking());
        }
    }

    public static function dispatchedEventProvider(): iterable
    {
        yield 'net per item' => [CalculateNetAmountPerItemEvent::class, 'calcNetAmountPerItem'];
        yield 'net total' => [CalculateNetTotalAmountEvent::class, 'calcNetTotalAmount'];
        yield 'tax value' => [GetTaxValueEvent::class, 'getTaxValue'];
        yield 'vat per item' => [CalculateVatAmountPerItemEvent::class, 'calcVatAmountPerItem'];
        yield 'vat total' => [CalculateVatTotalAmountEvent::class, 'calcVatTotalAmount'];
        yield 'gross per item' => [CalculateGrossAmountPerItemEvent::class, 'calcGrossAmountPerItem'];
        yield 'gross total' => [CalculateGrossTotalAmountEvent::class, 'calcGrossTotalAmount'];
    }

    /**
     * A listener overriding the net amount must ripple through vat and gross,
     * because those are derived from calcNetAmountPerItem().
     */
    public function testListenerOverridingTheNetAmountAffectsVatAndGross(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            CalculateNetAmountPerItemEvent::class,
            static function (CalculateNetAmountPerItemEvent $event): void {
                // 50 % discount for everyone.
                $event->setNetAmountPerItem($event->getNetAmountPerItem() / 2);
            },
        );

        $calEvent = $this->calEvent(netPrice: 100.0, taxValue: 20.0);
        $booking = $this->booking(2);
        $priceCalculator = $this->calculator($dispatcher);

        $this->assertSame(50.0, $priceCalculator->calcNetAmountPerItem($calEvent, $booking));
        $this->assertSame(10.0, $priceCalculator->calcVatAmountPerItem($calEvent, $booking));
        $this->assertSame(60.0, $priceCalculator->calcGrossAmountPerItem($calEvent, $booking));
        $this->assertSame(120.0, $priceCalculator->calcGrossTotalAmount($calEvent, $booking));
    }

    /**
     * The tax rate is overridable too - a listener may for instance apply a
     * reduced rate to a particular booking.
     */
    public function testListenerOverridingTheTaxValueAffectsTheVat(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            GetTaxValueEvent::class,
            static function (GetTaxValueEvent $event): void {
                $event->setTaxValue(2.5);
            },
        );

        $calEvent = $this->calEvent(netPrice: 200.0, taxValue: 20.0);
        $booking = $this->booking(2);
        $priceCalculator = $this->calculator($dispatcher);

        $this->assertSame(2.5, $priceCalculator->getTaxValue($calEvent, $booking));
        $this->assertSame(5.0, $priceCalculator->calcVatAmountPerItem($calEvent, $booking));
        $this->assertSame(10.0, $priceCalculator->calcVatTotalAmount($calEvent, $booking));
        $this->assertSame(410.0, $priceCalculator->calcGrossTotalAmount($calEvent, $booking));
    }

    /**
     * A listener writing back the total must win over the calculated value, and
     * the result still goes through formatPrice().
     */
    public function testListenerOverridingTheGrossTotalWinsAndIsRounded(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            CalculateGrossTotalAmountEvent::class,
            static function (CalculateGrossTotalAmountEvent $event): void {
                $event->setGrossTotalAmount(99.994999);
            },
        );

        $calEvent = $this->calEvent(netPrice: 100.0, taxValue: 20.0);

        $this->assertSame(
            99.99,
            $this->calculator($dispatcher)->calcGrossTotalAmount($calEvent, $this->booking(3)),
        );
    }

    /**
     * Documents how often each event fires during a single calcGrossTotalAmount()
     * call. The per-item net event fires twice, because the gross amount adds the
     * net amount to a vat amount that is itself derived from the net amount.
     *
     * Listeners must therefore be pure: doing anything with a side effect (writing
     * to the database, sending mail, incrementing a counter) in one of these
     * listeners will happen more often than the method name suggests.
     */
    public function testEventDispatchCounts(): void
    {
        $counts = [];

        $dispatcher = new EventDispatcher();

        foreach (array_keys(self::expectedDispatchCounts()) as $eventClass) {
            $counts[$eventClass] = 0;

            $dispatcher->addListener(
                $eventClass,
                static function () use (&$counts, $eventClass): void {
                    ++$counts[$eventClass];
                },
            );
        }

        $this->calculator($dispatcher)->calcGrossTotalAmount(
            $this->calEvent(netPrice: 100.0, taxValue: 20.0),
            $this->booking(2),
        );

        $this->assertSame(self::expectedDispatchCounts(), $counts);
    }

    /**
     * @return array<class-string, int>
     */
    private static function expectedDispatchCounts(): array
    {
        return [
            // calcGrossAmountPerItem() -> once directly, once through calcVatAmountPerItem()
            CalculateNetAmountPerItemEvent::class => 2,
            GetTaxValueEvent::class => 1,
            CalculateVatAmountPerItemEvent::class => 1,
            CalculateGrossAmountPerItemEvent::class => 1,
            CalculateGrossTotalAmountEvent::class => 1,
            // Not part of the gross total chain at all.
            CalculateNetTotalAmountEvent::class => 0,
            CalculateVatTotalAmountEvent::class => 0,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function calculator(EventDispatcherInterface|null $eventDispatcher = null): PriceCalculator
    {
        return new PriceCalculator($eventDispatcher ?? new EventDispatcher());
    }

    private function calEvent(float|string|null $netPrice = null, float|string|null $taxValue = null, string|null $currencyCode = null): CalendarEventsModel
    {
        return $this->createClassWithPropertiesStub(CalendarEventsModel::class, [
            'netPrice' => $netPrice,
            'taxValue' => $taxValue,
            'currencyCode' => $currencyCode,
        ]);
    }

    private function booking(int $ticketAmount = 1): CalendarEventsMemberModel
    {
        return $this->createClassWithPropertiesStub(
            CalendarEventsMemberModel::class,
            ['ticketAmount' => $ticketAmount],
        );
    }
}
