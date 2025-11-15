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

namespace Markocupic\CalendarEventBookingBundle\NotificationType;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\EmailTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\FileTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

#[AutoconfigureTag('cebb.notification')]
class EventBookingNotificationType implements NotificationTypeInterface, CalendarEventsBookingNotificationTypeInterface
{
    public const NAME = 'event-booking-notification';

    public function __construct(
        private readonly TokenDefinitionFactoryInterface $factory,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public static function getType(): string
    {
        return self::NAME;
    }

    /**
     * This makes the tokens available to the auto suggester in the notification
     * center. Setting the HTML tokens is not necessary! It even prevents the
     * auto-suggest feature from working properly.
     */
    public function getTokenDefinitions(): array
    {
        $tokenDefinitions = [];

        foreach ($this->getTokenConfig()['email_token'] ?? [] as $token) {
            $tokenDefinitions[] = $this->factory->create(EmailTokenDefinition::class, $token, 'event_booking.'.$token);
        }

        foreach ($this->getTokenConfig()['text_token'] ?? [] as $token) {
            $tokenDefinitions[] = $this->factory->create(TextTokenDefinition::class, $token, 'event_booking.'.$token);
        }

        foreach ($this->getTokenConfig()['file_token'] ?? [] as $token) {
            $tokenDefinitions[] = $this->factory->create(FileTokenDefinition::class, $token, 'event_booking.'.$token);
        }

        return $tokenDefinitions;
    }

    private function getTokenConfig(): array
    {
        return DefaultTokenConfig::getDefaultTokenConfig();
    }
}
