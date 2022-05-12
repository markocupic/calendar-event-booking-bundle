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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ValidateBookingRequest;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Message;
use Doctrine\DBAL\Exception;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook(ValidateNumberOfParticipants::HOOK, priority=ValidateNumberOfParticipants::PRIORITY)
 */
final class ValidateNumberOfParticipants
{
    public const HOOK = 'calEvtBookingValidateBookingRequest';
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
    private $evenRegistration;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator, EventRegistration $eventRegistration)
    {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->eventRegistration = $eventRegistration;
    }

    /**
     * Important! return false will make the validation fail
     * Validate if number of participants exceeds max member limit.
     *
     * @throws Exception
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance, array $arrDisabledHooks = []): bool
    {
        if (\in_array(self::class, $arrDisabledHooks, true)) {
            return true;
        }

        $messageAdapter = $this->framework->getAdapter(Message::class);

        /** @var Form $objForm */
        $objForm = $moduleInstance->getProperty('objForm');

        /** @var CalendarEventsModel $objEvent */
        $objEvent = $moduleInstance->getProperty('objEvent');

        // Check if user with submitted email has already booked
        $escorts = 0;

        if ($objEvent->includeEscortsWhenCalculatingRegCount && $objForm->hasFormField('escorts')) {
            $objWidget = $objForm->getWidget('escorts');
            $escorts = (int) $objWidget->value;
        }

        $countTotal = array_sum(
            [
                $this->eventRegistration->getBookingCount($objEvent),
                $escorts,
                1,
            ]
        );

        if ($this->eventRegistration->getBookingMax($objEvent) < $countTotal) {
            $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$objEvent->maxMembers], 'contao_default');
            $messageAdapter->addInfo($errorMsg);

            return false;
        }

        return true;
    }
}
