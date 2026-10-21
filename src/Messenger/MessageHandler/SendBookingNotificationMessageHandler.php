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

namespace Markocupic\CalendarEventBookingBundle\Messenger\MessageHandler;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Util\LocaleUtil;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Markocupic\CalendarEventBookingBundle\Messenger\Message\SendBookingNotificationMessage;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsOrderModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsPaymentModel;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class SendBookingNotificationMessageHandler
{
    private const CACHE_KEY_PREFIX = 'cebb.notification_sent.';

    /**
     * How long a sent message is remembered. Redeliveries happen within minutes; a
     * week is generous and still keeps the pool small.
     */
    private const SENT_TTL = 604800;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly NotificationService $notificationService,
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface|null $contaoErrorLogger = null,
    ) {
    }

    public function __invoke(SendBookingNotificationMessage $message): void
    {
        // Messenger delivers at least once, not exactly once: a worker that dies after
        // sending but before acknowledging hands the same message to the next one. The
        // id is remembered after sending, so that redelivery sends nothing. The window
        // between sending and remembering stays - it is a second wide instead of the
        // whole lifetime of a stuck message.
        $sent = $this->cache->getItem(self::CACHE_KEY_PREFIX.$message->messageId);

        if ($sent->isHit()) {
            return;
        }

        $this->framework->initialize();

        $booking = $this->framework->getAdapter(CalendarEventsMemberModel::class)->findById($message->bookingId);

        if (null === $booking) {
            // Deleted in the meantime. Retrying will not bring it back.
            $this->contaoErrorLogger?->error(\sprintf('Notification ID %d was not sent: booking ID %d no longer exists.', $message->notificationId, $message->bookingId));

            throw new UnrecoverableMessageHandlingException(\sprintf('Booking ID %d not found.', $message->bookingId));
        }

        $order = null !== $message->orderId ? $this->framework->getAdapter(CalendarEventsOrderModel::class)->findById($message->orderId) : null;
        $payment = null !== $message->paymentId ? $this->framework->getAdapter(CalendarEventsPaymentModel::class)->findById($message->paymentId) : null;

        $this->inLocale(
            $message->locale,
            function () use ($message, $booking, $order, $payment): void {
                $tokens = $this->notificationService->getNotificationTokens($booking, $order, $payment);
                $this->notificationService->sendNotification($message->notificationId, $tokens, $booking, $message->locale);
            },
        );

        $sent->set(true);
        $sent->expiresAfter(self::SENT_TTL);
        $this->cache->save($sent);
    }

    /**
     * Runs $callback in the language of the request that queued the notification.
     *
     * The notification center takes the locale as an argument, but the tokens are
     * built from Contao's language files - the salutation, for one - and those
     * follow $GLOBALS['TL_LANGUAGE'] and the translator. A worker that has just sent
     * a German notification goes on with the next message, so both are put back
     * afterwards.
     */
    private function inLocale(string|null $locale, \Closure $callback): void
    {
        if (null === $locale || '' === $locale) {
            $callback();

            return;
        }

        $translator = $this->translator instanceof LocaleAwareInterface ? $this->translator : null;

        $previousLanguage = $GLOBALS['TL_LANGUAGE'] ?? null;
        $previousLocale = $translator?->getLocale();

        $GLOBALS['TL_LANGUAGE'] = LocaleUtil::formatAsLanguageTag($locale);
        $translator?->setLocale(LocaleUtil::formatAsLocale($locale));

        try {
            $callback();
        } finally {
            if (null === $previousLanguage) {
                unset($GLOBALS['TL_LANGUAGE']);
            } else {
                $GLOBALS['TL_LANGUAGE'] = $previousLanguage;
            }

            // $previousLocale is only set when there is a locale aware translator.
            if (null !== $previousLocale) {
                $translator->setLocale($previousLocale);
            }
        }
    }
}
