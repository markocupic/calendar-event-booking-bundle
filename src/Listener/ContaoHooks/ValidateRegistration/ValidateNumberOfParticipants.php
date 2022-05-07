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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ValidateRegistration;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Message;
use Doctrine\DBAL\Exception;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook(ValidateNumberOfParticipants::HOOK, priority=ValidateNumberOfParticipants::PRIORITY)
 */
final class ValidateNumberOfParticipants extends AbstractHook
{
    public const HOOK = 'calEvtBookingValidateRegistration';
    public const PRIORITY = 1100;

    private ContaoFramework $framework;
    private TranslatorInterface $translator;
    private EventRegistration $eventRegistration;

    // Adapters
    private Adapter $message;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator, EventRegistration $eventRegistration)
    {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->eventRegistration = $eventRegistration;

        // Adapters
        $this->message = $framework->getAdapter(Message::class);
    }

    /**
     * Important! return false will make the validation fail
     * Validate if number of participants exceeds max member limit.
     *
     * @throws Exception
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        /** @var Form $objForm */
        $objForm = $moduleInstance->getProperty('objForm');

        /** @var EventConfig $eventConfig */
        $eventConfig = $moduleInstance->getProperty('eventConfig');

        // Check if user with submitted email has already booked
        $escorts = 0;

        if ($eventConfig->get('addEscortsToTotal') && $objForm->hasFormField('escorts')) {
            $objWidget = $objForm->getWidget('escorts');
            $escorts = (int) $objWidget->value;
        }

        $countTotal = array_sum(
            [
                $this->eventRegistration->getBookingCount($eventConfig),
                $escorts,
                1,
            ]
        );

        if ($eventConfig->getBookingMax() < $countTotal) {
            if ($this->eventRegistration->canAddToWaitingList($eventConfig, $escorts)) {
                return true;
            }

            $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$eventConfig->get('maxMembers')], 'contao_default');
            $this->message->addInfo($errorMsg);

            return false;
        }

        return true;
    }
}
