<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\NotificationType;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\EmailTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\HtmlTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

class EventBookingOptInInvitationNotificationType implements NotificationTypeInterface
{
    public const NAME = 'event-booking-opt-in-invitation-notification';

    public const TOKEN_CONFIG = [
        'text_token' => [
            //'member_optInLink',
        ],
        'html_token' => [
            //'member_optInLink',
        ],
        'email_token' => [
        ],
    ];

    public function __construct(
        private readonly TokenDefinitionFactoryInterface $factory,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        $tokenDefinitions = [];

        $tokens = array_merge($this->getTokenConfig()['text_token'], self::TOKEN_CONFIG['text_token']);

        foreach ($tokens as $token) {
            $tokenDefinitions[] = $this->factory->create(TextTokenDefinition::class, $token, 'event_booking.'.$token);
        }

        $tokens = array_merge($this->getTokenConfig()['html_token'], self::TOKEN_CONFIG['html_token']);

        foreach ($tokens as $token) {
            $tokenDefinitions[] = $this->factory->create(HtmlTokenDefinition::class, $token, 'event_booking.'.$token);
        }

        $tokens = array_merge($this->getTokenConfig()['email_token'], self::TOKEN_CONFIG['email_token']);

        foreach ($tokens as $token) {
            $tokenDefinitions[] = $this->factory->create(EmailTokenDefinition::class, $token, 'event_booking.'.$token);
        }

        return $tokenDefinitions;
    }

    private function getTokenConfig(): array
    {
        return DefaultTokenConfig::getDefaultTokenConfig();
    }
}
