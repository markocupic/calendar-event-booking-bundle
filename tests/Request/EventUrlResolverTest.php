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

namespace Markocupic\CalendarEventBookingBundle\Tests\Request;

use Contao\CalendarEventsModel;
use Contao\Input;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Request\EventUrlResolver;

class EventUrlResolverTest extends ContaoTestCase
{
    public function testResolvesEventFromEventsParameter(): void
    {
        $event = $this->createClassWithPropertiesStub(CalendarEventsModel::class, ['id' => 42]);

        $resolver = $this->resolver(
            store: ['events' => 'my-event'],
            findByIdOrAlias: ['my-event' => $event],
        );

        $this->assertSame($event, $resolver->resolve());
    }

    public function testFallsBackToAutoItemParameter(): void
    {
        $event = $this->createClassWithPropertiesStub(CalendarEventsModel::class, ['id' => 7]);

        // No "events" parameter, but an "auto_item" is present in the input.
        $resolver = $this->resolver(
            store: ['auto_item' => 'auto-event'],
            findByIdOrAlias: ['auto-event' => $event],
        );

        $this->assertSame($event, $resolver->resolve());
    }

    public function testEventsParameterTakesPrecedenceOverAutoItem(): void
    {
        $event = $this->createClassWithPropertiesStub(CalendarEventsModel::class, ['id' => 5]);

        // "events" is set, so "auto_item" must be ignored.
        $resolver = $this->resolver(
            store: ['events' => 'real-event', 'auto_item' => 'auto-event'],
            findByIdOrAlias: ['real-event' => $event],
        );

        $this->assertSame($event, $resolver->resolve());
    }

    public function testReturnsNullWhenNoEventGiven(): void
    {
        $resolver = $this->resolver(
            store: [],
            findByIdOrAlias: [],
        );

        $this->assertNull($resolver->resolve());
    }

    public function testReturnsNullWhenEventNotFound(): void
    {
        $resolver = $this->resolver(
            store: ['events' => 'unknown'],
            findByIdOrAlias: [],
        );

        $this->assertNull($resolver->resolve());
    }

    /**
     * @param array<string, mixed>               $store
     * @param array<string, CalendarEventsModel> $findByIdOrAlias
     */
    private function resolver(array $store, array $findByIdOrAlias): EventUrlResolver
    {
        $inputAdapter = $this->createAdapterStub(['get', 'setGet']);
        $inputAdapter
            ->method('get')
            ->willReturnCallback(
                static function (string $key) use (&$store) {
                    return $store[$key] ?? null;
                },
            )
        ;
        $inputAdapter
            ->method('setGet')
            ->willReturnCallback(
                static function (string $key, $value) use (&$store): void {
                    $store[$key] = $value;
                },
            )
        ;

        $eventsAdapter = $this->createAdapterStub(['findByIdOrAlias']);
        $eventsAdapter
            ->method('findByIdOrAlias')
            ->willReturnCallback(static fn ($idOrAlias) => $findByIdOrAlias[$idOrAlias] ?? null)
        ;

        $framework = $this->createContaoFrameworkStub([
            Input::class => $inputAdapter,
            CalendarEventsModel::class => $eventsAdapter,
        ]);

        return new EventUrlResolver($framework);
    }
}
