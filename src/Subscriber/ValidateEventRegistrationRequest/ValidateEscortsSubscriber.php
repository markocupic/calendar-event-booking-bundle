<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Subscriber\ValidateEventRegistrationRequest;

use Contao\CoreBundle\Framework\ContaoFramework;
use Markocupic\CalendarEventBookingBundle\Event\PostBookingEvent;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ValidateEscortsSubscriber implements EventSubscriberInterface
{
    public const PRIORITY = 1100;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventRegistration
     */
    private $eventRegistration;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator, EventRegistration $eventRegistration)
    {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->eventRegistration = $eventRegistration;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostBookingEvent::NAME => ['validateEscorts', self::PRIORITY],
        ];
    }

    /**
     * Important! Stop event propagtion if validation fails
     * Validate escorts.
     */
    public function validateEscorts(PostBookingEvent $event): void
    {
        if ($event->isDisabled(self::class)) {
            return;
        }

        $objForm = $event->getForm();
        $objEvent = $event->getEvent();

        if ($objForm->hasFormField('escorts')) {
            $objWidget = $objForm->getWidget('escorts');

            if ((int) $objWidget->value < 0) {
                $errorMsg = $this->translator->trans('MSC.enterPosIntVal', [], 'contao_default');
                $objWidget->addError($errorMsg);
            } elseif ($this->eventRegistration->isFullyBooked($objEvent)) {
                $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$objEvent->maxMembers], 'contao_default');
                $objWidget->addError($errorMsg);
            } elseif ((int) $objWidget->value > 0) {
                if ((int) $objWidget->value > (int) $objEvent->maxEscortsPerMember) {
                    $errorMsg = $this->translator->trans('MSC.maxEscortsPossible', [$objEvent->maxEscortsPerMember], 'contao_default');
                    $objWidget->addError($errorMsg);
                }
            }

            if ($objWidget->hasErrors()) {
                $event->stopPropagation();
            }
        }
    }
}
