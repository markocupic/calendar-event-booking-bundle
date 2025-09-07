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
use Markocupic\CalendarEventBookingBundle\NotificationType\EventBookingNotificationType;
use Markocupic\CalendarEventBookingBundle\NotificationType\EventBookingOptInInvitationNotificationType;
use Markocupic\CalendarEventBookingBundle\OptIn\OptIn;
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
use Terminal42\NotificationCenterBundle\Token\Definition\HtmlTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TokenDefinitionInterface;
use Terminal42\NotificationCenterBundle\Token\TokenCollection;
use Terminal42\NotificationCenterBundle\Util\Email;

class AddOptInTokenListener
{
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
        if (EventBookingOptInInvitationNotificationType::NAME !== $event->getNotificationType()->getName()) {
            return;
        }

        // Adding the member_optInLink token to the HtmlTokenDefinition will make the
        // auto suggester break!
        $event
            ->addTokenDefinition($this->getTokenDefinition(TextTokenDefinition::class, 'member_optInLink'))
        ;
    }

    /**
     * Set the opt-in link in the opt-in invitation notification.
     *
     * @todo The goal would be to use a different token for each message in the opt-in link
     * when there are multiple messages in the same notification. Unfortunately, this does
     * not work yet. See: https://contao.slack.com/archives/CK4J0KNDB/p1754637673245409
     */
    #[AsEventListener]
    public function onCreatParcel(CreateParcelEvent $event): void
    {
        $parcel = $event->getParcel();

        $notificationConfig = $parcel->getStamp(NotificationConfigStamp::class);

        if (!$notificationConfig instanceof NotificationConfigStamp) {
            return;
        }

        $allowed = [EventBookingOptInInvitationNotificationType::NAME, EventBookingNotificationType::NAME];

        if (!\in_array($notificationConfig->toArray()['type'], $allowed, true)) {
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

        $booking = CalendarEventsMemberModel::findOneBy('bookingToken', $uuid);

        if (null === $booking) {
            return;
        }

        $optInToken = OptIn::generateToken();
        $optInLink = $this->optInLinkBuilder->build($booking, $optInToken);
        $event->getParcel()->getStamp(TokenCollectionStamp::class)->tokenCollection
            ->addToken($this->getTokenDefinition(TextTokenDefinition::class, 'member_optInLink')->createToken('member_optInLink', $optInLink))
            ->addToken($this->getTokenDefinition(HtmlTokenDefinition::class, 'member_optInLink')->createToken('member_optInLink', $optInLink))
        ;

        // Create the opt-in entries in tl_opt_in
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

        if (empty($calendar->requireOptIn)) {
            return;
        }

        $languageConfig = $parcel->getStamp(LanguageConfigStamp::class);
        $tokenCollection = $parcel->getStamp(TokenCollectionStamp::class);

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

        (new OptIn($this->framework))
            ->create($optIn['token'], $removeOn, $optIn['email'], $optIn['email_subject'], $optIn['email_text'], $related)
        ;
    }

    private function getTokenDefinition(string $tokenDefinition, string $token): TokenDefinitionInterface
    {
        return $this->tokenDefinitionFactory->create($tokenDefinition, $token, $token);
    }
}
