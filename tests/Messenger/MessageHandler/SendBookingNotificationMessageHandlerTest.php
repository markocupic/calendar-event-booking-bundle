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

namespace Markocupic\CalendarEventBookingBundle\Tests\Messenger\MessageHandler;

use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Domain\Notification\NotificationService;
use Markocupic\CalendarEventBookingBundle\Messenger\Message\SendBookingNotificationMessage;
use Markocupic\CalendarEventBookingBundle\Messenger\MessageHandler\SendBookingNotificationMessageHandler;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsOrderModel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsPaymentModel;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendBookingNotificationMessageHandlerTest extends ContaoTestCase
{
    private mixed $previousLanguage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousLanguage = $GLOBALS['TL_LANGUAGE'] ?? null;
    }

    protected function tearDown(): void
    {
        if (null === $this->previousLanguage) {
            unset($GLOBALS['TL_LANGUAGE']);
        } else {
            $GLOBALS['TL_LANGUAGE'] = $this->previousLanguage;
        }

        parent::tearDown();
    }

    /**
     * The tokens are built when the message is handled, from the records as they are
     * then - and in the language of the request that queued the notification, which
     * the worker does not have. Afterwards the worker is back in its own language,
     * because the next message may be for somebody else.
     */
    // LocaleUtil needs intl, which every Contao installation has.
    #[RequiresPhpExtension('intl')]
    public function testItSendsInTheQueuedLanguageAndRestoresTheWorkersOwn(): void
    {
        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['id' => 7]);
        $order = $this->createClassWithPropertiesStub(CalendarEventsOrderModel::class, ['id' => 11]);
        $payment = $this->createClassWithPropertiesStub(CalendarEventsPaymentModel::class, ['id' => 13]);

        $translator = new Translator('en');
        $GLOBALS['TL_LANGUAGE'] = 'en';

        $whileBuilding = [];

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService
            ->expects($this->once())
            ->method('getNotificationTokens')
            ->with($booking, $order, $payment)
            ->willReturnCallback(
                static function () use (&$whileBuilding, $translator): array {
                    $whileBuilding = [$GLOBALS['TL_LANGUAGE'], $translator->getLocale()];

                    return ['member_firstname' => 'Anna'];
                },
            )
        ;

        $notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(5, ['member_firstname' => 'Anna'], $booking, 'de_CH')
        ;

        $handler = $this->handler($notificationService, $this->models($booking, $order, $payment), translator: $translator);
        $handler(new SendBookingNotificationMessage('m-1', 5, 7, 11, 13, 'de_CH'));

        $this->assertSame(['de-CH', 'de_CH'], $whileBuilding);
        $this->assertSame('en', $GLOBALS['TL_LANGUAGE']);
        $this->assertSame('en', $translator->getLocale());
    }

    /**
     * Messenger delivers at least once. A worker that dies after sending but before
     * acknowledging hands the message on - the participant must not get it twice.
     */
    public function testARedeliveredMessageIsNotSentAgain(): void
    {
        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['id' => 7]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService
            ->expects($this->once())
            ->method('sendNotification')
        ;

        $handler = $this->handler($notificationService, $this->models($booking));

        $message = new SendBookingNotificationMessage('m-1', 5, 7);
        $handler($message);
        $handler($message);
    }

    /**
     * Only a sent notification is remembered. A failed one is left to messenger's
     * retries, and the retry must not find it marked as done.
     */
    public function testAFailedSendIsRetriedNotForgotten(): void
    {
        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['id' => 7]);

        $attempts = 0;

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService
            ->expects($this->exactly(2))
            ->method('sendNotification')
            ->willReturnCallback(
                static function () use (&$attempts): null {
                    if (1 === ++$attempts) {
                        throw new \RuntimeException('Notification center not ready');
                    }

                    return null;
                },
            )
        ;

        $handler = $this->handler($notificationService, $this->models($booking));
        $message = new SendBookingNotificationMessage('m-1', 5, 7);

        try {
            $handler($message);
            $this->fail('The first attempt should have failed.');
        } catch (\RuntimeException) {
        }

        $handler($message);
    }

    /**
     * A booking deleted in the meantime will not come back by retrying, so the
     * message is given up at once instead of going through every retry.
     */
    public function testADeletedBookingIsGivenUpAtOnce(): void
    {
        $notificationService = $this->createMock(NotificationService::class);
        $notificationService
            ->expects($this->never())
            ->method('sendNotification')
        ;

        $this->expectException(UnrecoverableMessageHandlingException::class);

        $this->handler($notificationService, $this->models(null))(new SendBookingNotificationMessage('m-1', 5, 7));
    }

    /**
     * Without a locale - queued outside a request, by a cron job - the language is
     * left alone and the notification center falls back to the notification's own.
     */
    public function testWithoutALocaleTheLanguageIsLeftAlone(): void
    {
        $booking = $this->createClassWithPropertiesStub(CalendarEventsMemberModel::class, ['id' => 7]);
        $GLOBALS['TL_LANGUAGE'] = 'fr';

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(5, [], $booking, null)
        ;

        $this->handler($notificationService, $this->models($booking))(new SendBookingNotificationMessage('m-1', 5, 7));

        $this->assertSame('fr', $GLOBALS['TL_LANGUAGE']);
    }

    private function handler(NotificationService $notificationService, array $adapters, TranslatorInterface|null $translator = null): SendBookingNotificationMessageHandler
    {
        return new SendBookingNotificationMessageHandler(
            $this->createContaoFrameworkStub($adapters),
            $notificationService,
            $translator ?? $this->createStub(TranslatorInterface::class),
            new ArrayAdapter(),
            null,
        );
    }

    private function models(CalendarEventsMemberModel|null $booking, CalendarEventsOrderModel|null $order = null, CalendarEventsPaymentModel|null $payment = null): array
    {
        $adapters = [];

        foreach ([CalendarEventsMemberModel::class => $booking, CalendarEventsOrderModel::class => $order, CalendarEventsPaymentModel::class => $payment] as $class => $model) {
            $adapter = $this->createAdapterStub(['findById']);
            $adapter
                ->method('findById')
                ->willReturn($model)
            ;

            $adapters[$class] = $adapter;
        }

        return $adapters;
    }
}
