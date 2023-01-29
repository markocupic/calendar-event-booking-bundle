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

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\ValidateRegistration;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration\EventRegistration;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook(ValidateEmailAddress::HOOK, priority: 1000)]
final class ValidateEmailAddress extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_VALIDATE_REGISTRATION;

    // Adapters
    private Adapter $eventMember;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly EventRegistration $eventRegistration,
    ) {
        // Adapters
        $this->eventMember = $this->framework->getAdapter(CalendarEventsMemberModel::class);
    }

    /**
     * Important! return false will make the validation fail
     * Validate email address.
     *
     * @throws \Exception
     */
    public function __invoke(EventRegistration $eventRegistration, EventConfig $eventConfig): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        $request = $this->requestStack->getCurrentRequest();

        $form = $eventRegistration->getForm();

        // Check if user with submitted email has already booked
        if ($form->hasFormField('email')) {
            $objWidget = $form->getWidget('email');

            if (!empty($objWidget->value)) {
                if (!$eventConfig->get('allowDuplicateEmail')) {
                    $t = $this->eventRegistration->getTable();

                    $arrOptions = [
                        'column' => [$t.'.email = ?', $t.'.pid = ?'],
                        'value' => [strtolower($objWidget->value), $eventConfig->getModel()->id],
                    ];

                    $objMember = $this->eventMember->findAll($arrOptions);

                    if (null !== $objMember) {
                        $errorMsg = $this->translator->trans('MSC.you_have_already_subscribed_to_this_event', [$request->request->get('email')], 'contao_default');
                        $objWidget->addError($errorMsg);

                        // Return false will make the validation fail
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
