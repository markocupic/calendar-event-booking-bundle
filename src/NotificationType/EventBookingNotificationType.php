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
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

class EventBookingNotificationType implements NotificationTypeInterface
{
    public const NAME = 'event-booking-notification';

    public function __construct(private readonly TokenDefinitionFactoryInterface $factory)
    {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        $tokenDefinitions = [];

        foreach ($this->getTokenConfig()['text_token'] as $token) {
            $tokenDefinitions[] = $this->factory->create(TextTokenDefinition::class, $token, 'event_booking.'.$token);
        }

        foreach ($this->getTokenConfig()['email_token'] as $token) {
            $tokenDefinitions[] = $this->factory->create(EmailTokenDefinition::class, $token, 'event_booking.'.$token);
        }

        return $tokenDefinitions;
    }

    private function getTokenConfig(): array
    {
        return [
            'email_token' => [
                'organizer_email',
                'member_email',
                'admin_email',
            ],
            'text_token' => [
                'event_*',
                'event_title',
                'event_unsubscribeLimitTstamp',
                'event_unsubscribeHref',
                'event_startDateFormatted',
                'event_endDateFormatted',
                'event_startTimeFormatted',
                'event_endTimeFormatted',
                'member_*',
                'member_dateOfBirth',
                'member_salutation',
                'organizer_*',
                'organizer_name',
                'organizer_email',
                'admin_email',
            ],
        ];
    }
}
