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
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use PHPUnit\Framework\Attributes\DataProvider;

class PriceCalculatorTest extends ContaoTestCase
{
    public function testCalcGrossAmountPerItem(): void
    {
        // Create a mock CalendarEventsModel with predefined values
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->netPrice = 100.0;
        $event->taxValue = 20.0;

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Expected calculations
        $expectedVat = 100.0 * 20.0 / 100.0;
        $expectedGrossAmount = 100.0 + $expectedVat;

        // Execute the method
        $grossAmount = $priceCalculator->calcGrossAmountPerItem($event);

        // Verify the result
        $this->assertSame(round($expectedGrossAmount, 2), $grossAmount);
    }

    /**
     * Test the getCurrencyCode method of the PriceCalculator class.
     */
    public function testGetCurrencyCode(): void
    {
        // Create a mock CalendarEventsModel with a predefined currency code
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->currencyCode = 'USD';

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Execute the method
        $currencyCode = $priceCalculator->getCurrencyCode($event);

        // Verify the result
        $this->assertSame('USD', $currencyCode);
    }

    /**
     * Test the getCurrencyCode method when currency code is null.
     */
    public function testGetCurrencyCodeWhenNull(): void
    {
        // Create a mock CalendarEventsModel without a currency code
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->currencyCode = null;

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Execute the method
        $currencyCode = $priceCalculator->getCurrencyCode($event);

        // Verify the result
        $this->assertSame('', $currencyCode);
    }

    public function testCalcGrossTotalAmount(): void
    {
        // Create a mock CalendarEventsModel with predefined values
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->netPrice = 100.0;
        $event->taxValue = 20.0;

        // Create a mock CalendarEventsMemberModel with ticket amount
        $booking = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class);
        $booking->ticketAmount = 3;

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Expected calculations
        $expectedVat = 100.0 * 20.0 / 100.0;
        $expectedGrossAmount = 100.0 + $expectedVat;
        $expectedGrossTotalAmount = $booking->ticketAmount * $expectedGrossAmount;

        // Execute the method
        $grossTotalAmount = $priceCalculator->calcGrossTotalAmount($event, $booking);

        // Verify the result
        $this->assertSame(round($expectedGrossTotalAmount, 2), $grossTotalAmount);
    }

    public function testCalcNetAmountPerItem(): void
    {
        // Create a mock CalendarEventsModel with predefined values
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->netPrice = 200.0;

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Expected result
        $expectedNetAmount = 200.0;

        // Execute the method
        $netAmount = $priceCalculator->calcNetAmountPerItem($event);

        // Verify the result
        $this->assertSame(round($expectedNetAmount, 2), $netAmount);
    }

    /**
     * Contao models return DOUBLE columns (netPrice) as strings. Because the class
     * declares strict_types, the calculator must cast the raw model value to float
     * before handing it to formatPrice(float) - otherwise a TypeError is thrown.
     */
    public function testCalcNetAmountPerItemWithStringNetPrice(): void
    {
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->netPrice = '150.00';

        $priceCalculator = new PriceCalculator();

        $this->assertSame(150.0, $priceCalculator->calcNetAmountPerItem($event));
    }

    /**
     * Guards the whole per-item chain (net -> vat -> gross) against string model
     * values, mirroring what a real Contao model returns from the database.
     */
    public function testCalcGrossAmountPerItemWithStringModelValues(): void
    {
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->netPrice = '100.00';
        $event->taxValue = '20.00';

        $priceCalculator = new PriceCalculator();

        $this->assertSame(20.0, $priceCalculator->calcVatAmountPerItem($event));
        $this->assertSame(120.0, $priceCalculator->calcGrossAmountPerItem($event));
    }

    public function testCalcNetTotalAmount(): void
    {
        // Create a mock CalendarEventsModel with predefined values
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->netPrice = 150.0;

        // Create a mock CalendarEventsMemberModel with ticket amounts
        $booking1 = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class);
        $booking1->ticketAmount = 1;

        $booking2 = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class);
        $booking2->ticketAmount = 5;

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Expected results
        $expectedNetTotalAmount1 = $booking1->ticketAmount * $event->netPrice;
        $expectedNetTotalAmount2 = $booking2->ticketAmount * $event->netPrice;

        // Execute the method and verify results
        $netTotalAmount1 = $priceCalculator->calcNetTotalAmount($event, $booking1);
        $this->assertSame(round($expectedNetTotalAmount1, 2), $netTotalAmount1);

        $netTotalAmount2 = $priceCalculator->calcNetTotalAmount($event, $booking2);
        $this->assertSame(round($expectedNetTotalAmount2, 2), $netTotalAmount2);
    }

    /**
     * Test the calcVatAmountPerItem method of the PriceCalculator class.
     */
    public function testCalcVatAmountPerItem(): void
    {
        // Create a mock CalendarEventsModel with predefined values
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->netPrice = 100.0;
        $event->taxValue = 20.0;

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Expected VAT calculation
        $expectedVatAmount = 100.0 * 20.0 / 100.0;

        // Execute the method
        $vatAmount = $priceCalculator->calcVatAmountPerItem($event);

        // Verify the result
        $this->assertSame(round($expectedVatAmount, 2), $vatAmount);
    }

    /**
     * Test the getTaxValue method of the PriceCalculator class.
     */
    public function testGetTaxValue(): void
    {
        // Create a mock CalendarEventsModel with a predefined tax value
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->taxValue = 15.0;

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Execute the method
        $taxValue = $priceCalculator->getTaxValue($event);

        // Verify the result
        $this->assertSame(15.0, $taxValue);
    }

    /**
     * Test the getTaxValue method when the taxValue property is null.
     */
    public function testGetTaxValueWhenNull(): void
    {
        // Create a mock CalendarEventsModel with a null tax value
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->taxValue = null;

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Execute the method
        $taxValue = $priceCalculator->getTaxValue($event);

        // Verify the result
        $this->assertSame(0.0, $taxValue);
    }

    /**
     * Test the calcVatTotalAmount method of the PriceCalculator class.
     */
    public function testCalcVatTotalAmount(): void
    {
        // Create a mock CalendarEventsModel with predefined values
        $event = $this->createClassWithPropertiesMock(CalendarEventsModel::class);
        $event->netPrice = 120.0;
        $event->taxValue = 15.0;

        // Create mock CalendarEventsMemberModels with ticket amounts
        $booking1 = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class);
        $booking1->ticketAmount = 2;

        $booking2 = $this->createClassWithPropertiesMock(CalendarEventsMemberModel::class);
        $booking2->ticketAmount = 5;

        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Expected results
        $expectedVatPerItem = 120.0 * 15.0 / 100.0;
        $expectedVatTotal1 = $expectedVatPerItem * $booking1->ticketAmount;
        $expectedVatTotal2 = $expectedVatPerItem * $booking2->ticketAmount;

        // Execute the method and verify results
        $vatTotalAmount1 = $priceCalculator->calcVatTotalAmount($event, $booking1);
        $this->assertSame(round($expectedVatTotal1, 2), $vatTotalAmount1);

        $vatTotalAmount2 = $priceCalculator->calcVatTotalAmount($event, $booking2);
        $this->assertSame(round($expectedVatTotal2, 2), $vatTotalAmount2);
    }

    #[DataProvider('formatPrice')]
    public function testFormatPrice(float $test, float $expected): void
    {
        // Create an instance of PriceCalculator
        $priceCalculator = new PriceCalculator();

        // Execute the method
        $method = new \ReflectionMethod(PriceCalculator::class, 'formatPrice');
        $result = $method->invokeArgs($priceCalculator, [$test]);

        // Verify the result
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
}
