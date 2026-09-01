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

namespace Markocupic\CalendarEventBookingBundle\Tests\EventListener\NotificationCenter;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\EventListener\NotificationCenter\AddOptInTokenListener;
use Markocupic\CalendarEventBookingBundle\LinkBuilder\OptInLinkBuilder;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationType\EventBookingNotificationType;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationType\EventBookingOptInInvitationNotificationType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\Event\GetTokenDefinitionsForNotificationTypeEvent;
use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TokenDefinitionInterface;

/**
 * Only the onGetTokenDefinitions guard is unit-testable here. The onCreatParcel
 * method is tightly coupled to the Notification Center parcel/stamp pipeline and
 * belongs in a functional test.
 */
class AddOptInTokenListenerTest extends ContaoTestCase
{
    #[DataProvider('supportedNotificationTypesProvider')]
    public function testAddsOptInTokenDefinitionForSupportedNotificationType(string $notificationType): void
    {
        $tokenDefinition = $this->createStub(TokenDefinitionInterface::class);

        $factory = $this->createMock(TokenDefinitionFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(TextTokenDefinition::class, 'member_optInLink', 'member_optInLink')
            ->willReturn($tokenDefinition)
        ;

        $event = $this->createMock(GetTokenDefinitionsForNotificationTypeEvent::class);
        $event
            ->method('getNotificationType')
            ->willReturn($this->mockNotificationType($notificationType))
        ;

        $event
            ->expects($this->once())
            ->method('addTokenDefinition')
            ->with($tokenDefinition)
        ;

        $this->listener($factory)->onGetTokenDefinitions($event);
    }

    public function testIgnoresOtherNotificationTypes(): void
    {
        $factory = $this->createMock(TokenDefinitionFactoryInterface::class);
        $factory
            ->expects($this->never())
            ->method('create')
        ;

        $event = $this->createMock(GetTokenDefinitionsForNotificationTypeEvent::class);
        $event
            ->method('getNotificationType')
            ->willReturn($this->mockNotificationType('some-other-notification'))
        ;

        $event
            ->expects($this->never())
            ->method('addTokenDefinition')
        ;

        $this->listener($factory)->onGetTokenDefinitions($event);
    }

    /**
     * The supported types are listed explicitly and not read from
     * AddOptInTokenListener::SUPPORTED_NOTIFICATION_TYPES on purpose: A test should not
     * verify a constant against itself, otherwise the accidental removal of a
     * notification type would go unnoticed.
     */
    public static function supportedNotificationTypesProvider(): iterable
    {
        yield EventBookingNotificationType::NAME => [EventBookingNotificationType::NAME];

        yield EventBookingOptInInvitationNotificationType::NAME => [EventBookingOptInInvitationNotificationType::NAME];
    }

    private function mockNotificationType(string $name): NotificationTypeInterface
    {
        $type = $this->createStub(NotificationTypeInterface::class);
        $type
            ->method('getName')
            ->willReturn($name)
        ;

        return $type;
    }

    private function listener(TokenDefinitionFactoryInterface $factory): AddOptInTokenListener
    {
        return new AddOptInTokenListener(
            $this->createStub(ContaoFramework::class),
            $this->createStub(InsertTagParser::class),
            $this->createStub(OptInLinkBuilder::class),
            $this->createStub(RequestStack::class),
            $this->createStub(SimpleTokenParser::class),
            $factory,
            86400,
        );
    }
}
