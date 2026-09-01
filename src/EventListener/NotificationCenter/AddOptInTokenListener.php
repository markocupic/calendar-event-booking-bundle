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

namespace Markocupic\CalendarEventBookingBundle\EventListener\NotificationCenter;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\StringUtil;
use Markocupic\CalendarEventBookingBundle\LinkBuilder\OptInLinkBuilder;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationType\EventBookingNotificationType;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationType\EventBookingOptInInvitationNotificationType;
use Markocupic\CalendarEventBookingBundle\Notification\NotificationType\EventBookingPaymentSuccessNotificationType;
use Markocupic\CalendarEventBookingBundle\OptIn\OptInTokenCreator;
use Soundasleep\Html2Text;
use Soundasleep\Html2TextException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\Event\CreateParcelEvent;
use Terminal42\NotificationCenterBundle\Event\GetTokenDefinitionsForNotificationTypeEvent;
use Terminal42\NotificationCenterBundle\Parcel\Parcel;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\LanguageConfigStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\NotificationConfigStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\TokenCollectionStamp;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TokenDefinitionInterface;
use Terminal42\NotificationCenterBundle\Token\TokenCollection;
use Terminal42\NotificationCenterBundle\Util\Email;

class AddOptInTokenListener
{
    private const array SUPPORTED_NOTIFICATION_TYPES = [
        EventBookingNotificationType::NAME,
        EventBookingOptInInvitationNotificationType::NAME,
        EventBookingPaymentSuccessNotificationType::NAME,
    ];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly InsertTagParser $insertTagParser,
        private readonly OptInLinkBuilder $optInLinkBuilder,
        private readonly RequestStack $requestStack,
        private readonly SimpleTokenParser $simpleTokenParser,
        private readonly TokenDefinitionFactoryInterface $tokenDefinitionFactory,
        private readonly int $autoExpireTimeLimit,
    ) {
    }

    #[AsEventListener]
    public function onGetTokenDefinitions(GetTokenDefinitionsForNotificationTypeEvent $event): void
    {
        if (!\in_array($event->getNotificationType()->getName(), self::SUPPORTED_NOTIFICATION_TYPES, true)) {
            return;
        }

        // Adding the member_optInLink token to the HtmlTokenDefinition will make the
        // auto suggester break!
        $event->addTokenDefinition($this->getTokenDefinition(TextTokenDefinition::class, 'member_optInLink'));
    }

    /**
     * Set the opt-in link in the supported notifications.
     *
     * Every message of a notification gets its own opt-in token.
     *
     * Beware: All parcels of a notification share the very same TokenCollectionStamp
     * instance, because NotificationCenter::createParcelsForNotification() passes the
     * same StampCollection to every message and Parcel::withStamp() clones the parcel
     * but not the stamp. Mutating $stamp->tokenCollection directly would therefore
     * overwrite the token of all the other messages as well. That is why we work on a
     * clone of the token collection and put it back onto the parcel using a new stamp.
     */
    #[AsEventListener]
    public function onCreatParcel(CreateParcelEvent $event): void
    {
        $parcel = $event->getParcel();

        $notificationConfigStamp = $parcel->getStamp(NotificationConfigStamp::class);

        if (!$notificationConfigStamp instanceof NotificationConfigStamp) {
            return;
        }

        if (!\in_array($notificationConfigStamp->notificationConfig->getType(), self::SUPPORTED_NOTIFICATION_TYPES, true)) {
            return;
        }

        $tokenCollectionStamp = $parcel->getStamp(TokenCollectionStamp::class);

        if (!$tokenCollectionStamp instanceof TokenCollectionStamp) {
            return;
        }

        $uuid = $this->requestStack->getCurrentRequest()?->attributes->get('_calendar_event_booking_token');

        if (empty($uuid)) {
            return;
        }

        $booking = $this->framework->getAdapter(CalendarEventsMemberModel::class)->findOneBy('bookingToken', $uuid);

        if (null === $booking) {
            return;
        }

        // The goal is to use a different opt-in token for each message,
        // so we can determine who has confirmed the booking.
        $optInToken = OptInTokenCreator::generateToken();
        $optInLink = $this->optInLinkBuilder->build($booking, $optInToken);

        // Work on a clone, see the note in the doc block above.
        $tokenCollection = clone $tokenCollectionStamp->tokenCollection;

        $tokenCollection->replaceToken(
            $this->getTokenDefinition(TextTokenDefinition::class, 'member_optInLink')->createToken('member_optInLink', $optInLink),
        );

        $parcel = $parcel->withStamp(new TokenCollectionStamp($tokenCollection));

        $event->setParcel($parcel);

        // Create the opt-in entries in tl_opt_in.
        // Beware: This has to run on the updated parcel, otherwise the ##member_optInLink##
        // token would remain unreplaced in the email text stored in tl_opt_in.
        $this->addOptInIfRequired($parcel, $booking, $optInToken);
    }

    private function replaceTokens(string $value, TokenCollection $tokenCollection): string
    {
        return $this->simpleTokenParser->parse($value, $tokenCollection->forSimpleTokenParser());
    }

    private function replaceInsertTags(string $value): string
    {
        return $this->insertTagParser->replaceInline($value);
    }

    private function replaceTokensAndInsertTags(string $value, TokenCollection $tokenCollection): string
    {
        return $this->replaceInsertTags($this->replaceTokens($value, $tokenCollection));
    }

    /**
     * Register the opt-in token in tl_opt_in.
     *
     * @throws Html2TextException
     */
    private function addOptInIfRequired(Parcel $parcel, CalendarEventsMemberModel $booking, string $optInToken): void
    {
        $calendar = $booking->getRelated('pid')?->getRelated('pid');

        if (null === $calendar || !$calendar->allowEventBooking || !$calendar->requireOptIn) {
            return;
        }

        $languageConfig = $parcel->getStamp(LanguageConfigStamp::class);

        // There is no language configuration for this message and locale,
        // thus the message will not be sent at all.
        if (!$languageConfig instanceof LanguageConfigStamp) {
            return;
        }

        $tokenCollection = $parcel->getStamp(TokenCollectionStamp::class);

        if (!$tokenCollection instanceof TokenCollectionStamp) {
            return;
        }

        $optIn = [];
        $optIn['token'] = $optInToken;

        // Get the email addresses
        $recipients = $this->replaceTokensAndInsertTags($languageConfig->languageConfig->getString('recipients'), $tokenCollection->tokenCollection);
        $optIn['email'] = implode(',', Email::splitEmailAddresses($recipients));

        // Get the email subject
        $optIn['email_subject'] = $this->replaceTokensAndInsertTags($languageConfig->languageConfig->getString('email_subject'), $tokenCollection->tokenCollection);

        // Get the email text
        $optIn['email_text'] = '';

        switch ($languageConfig->languageConfig->getString('email_mode')) {
            case 'textAndHtml':
            case 'textOnly':
                $optIn['email_text'] = $this->replaceTokensAndInsertTags($languageConfig->languageConfig->getString('email_text'), $tokenCollection->tokenCollection);
                break;
            case 'htmlAndAutoText':
                $html = $this->replaceTokensAndInsertTags(StringUtil::restoreBasicEntities($languageConfig->languageConfig->getString('email_html')), $tokenCollection->tokenCollection);
                $optIn['email_text'] = Html2Text::convert($html);
                break;
        }

        $related = [];
        $related[CalendarEventsMemberModel::getTable()] = [$booking->id];

        $removeOn = time() + $this->autoExpireTimeLimit;

        (new OptInTokenCreator($this->framework))
            ->create($optIn['token'], $removeOn, $optIn['email'], $optIn['email_subject'], $optIn['email_text'], $related)
        ;
    }

    private function getTokenDefinition(string $tokenDefinition, string $token): TokenDefinitionInterface
    {
        return $this->tokenDefinitionFactory->create($tokenDefinition, $token, $token);
    }
}
