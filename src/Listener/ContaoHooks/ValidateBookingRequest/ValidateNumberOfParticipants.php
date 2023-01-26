<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\ValidateBookingRequest;

use Codefog\HasteBundle\Form\Form;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Message;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Helper\EventRegistration;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook(ValidateNumberOfParticipants::HOOK, priority: 1100)]
final class ValidateNumberOfParticipants
{
    public const HOOK = 'calEvtBookingValidateBookingRequest';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly EventRegistration $eventRegistration,
    ) {
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

        if ($this->eventRegistration->getBookingMax($objEvent) < $countTotal && (int) $objEvent->maxMembers > 0) {
            $errorMsg = $this->translator->trans('MSC.maxMemberLimitExceeded', [$objEvent->maxMembers], 'contao_default');
            $messageAdapter->addInfo($errorMsg);

            return false;
        }

        return true;
    }
}
