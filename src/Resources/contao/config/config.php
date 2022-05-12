<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;

// Add child table tl_calendar_events_member to tl_calendar_events
$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_calendar_events_member';

if (TL_MODE === 'BE') {
    // Add Backend CSS
    $GLOBALS['TL_CSS'][] = 'bundles/markocupiccalendareventbooking/css/be_stylesheet.css';
}

// Register custom models
$GLOBALS['TL_MODELS']['tl_calendar_events_member'] = CalendarEventsMemberModel::class;

/*
 * Notification center
 */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['calendar-event-booking-bundle'] = [
    'booking-notification' => [
        'email_sender_name' => ['sender_name'],
        'email_sender_address' => ['sender_email', 'admin_email'],
        'recipients' => ['sender_email', 'member_email', 'admin_email'],
        'email_recipient_cc' => ['sender_email', 'member_email', 'admin_email'],
        'email_recipient_bcc' => ['sender_email', 'member_email', 'admin_email'],
        'email_replyTo' => ['sender_email', 'member_email', 'admin_email'],
        'email_subject' => ['event_*', 'event_title', 'event_unsubscribeLimitTstamp', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirth', 'member_salutation', 'sender_*', 'sender_name', 'sender_email', 'admin_email'],
        'email_text' => ['event_*', 'event_title', 'event_unsubscribeLimitTstamp', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirth', 'member_salutation', 'sender_*', 'sender_name', 'sender_email', 'admin_email'],
        'email_html' => ['event_*', 'event_title', 'event_unsubscribeLimitTstamp', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirth', 'member_salutation', 'sender_*', 'sender_name', 'sender_email', 'admin_email'],
        'attachment_tokens' => [],
    ],
    'event-unsubscribe-notification' => [
        'email_sender_name' => ['sender_name'],
        'email_sender_address' => ['sender_email', 'admin_email'],
        'recipients' => ['sender_email', 'member_email', 'admin_email'],
        'email_recipient_cc' => ['sender_email', 'member_email', 'admin_email'],
        'email_recipient_bcc' => ['sender_email', 'member_email', 'admin_email'],
        'email_replyTo' => ['sender_email', 'member_email', 'admin_email'],
        'email_subject' => ['event_*', 'event_title', 'event_unsubscribeLimitTstamp', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirth', 'member_salutation', 'sender_*', 'sender_name', 'sender_email', 'admin_email'],
        'email_text' => ['event_*', 'event_title', 'event_unsubscribeLimitTstamp', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirth', 'member_salutation', 'sender_*', 'sender_name', 'sender_email', 'admin_email'],
        'email_html' => ['event_*', 'event_title', 'event_unsubscribeLimitTstamp', 'event_unsubscribeHref', 'member_*', 'member_dateOfBirth', 'member_salutation', 'sender_*', 'sender_name', 'sender_email', 'admin_email'],
        'attachment_tokens' => [],
    ],
];
