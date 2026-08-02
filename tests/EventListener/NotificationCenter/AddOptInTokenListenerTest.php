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
use Markocupic\CalendarEventBookingBundle\Notification\NotificationType\EventBookingOptInInvitationNotificationType;
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
    public function testAddsOptInTokenDefinitionForMatchingNotificationType(): void
    {
        $tokenDefinition = $this->createMock(TokenDefinitionInterface::class);

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
            ->willReturn($this->mockNotificationType(EventBookingOptInInvitationNotificationType::NAME))
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

    private function mockNotificationType(string $name): NotificationTypeInterface
    {
        $type = $this->createMock(NotificationTypeInterface::class);
        $type
            ->method('getName')
            ->willReturn($name)
        ;

        return $type;
    }

    private function listener(TokenDefinitionFactoryInterface $factory): AddOptInTokenListener
    {
        return new AddOptInTokenListener(
            $this->createMock(ContaoFramework::class),
            $this->createMock(InsertTagParser::class),
            $this->createMock(OptInLinkBuilder::class),
            $this->createMock(RequestStack::class),
            $this->createMock(SimpleTokenParser::class),
            $factory,
            86400,
        );
    }
}
