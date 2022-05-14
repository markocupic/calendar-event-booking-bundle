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
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventBooking\EventSubscriber\EventSubscriber;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook(ValidateEmailAddress::HOOK, priority=ValidateEmailAddress::PRIORITY)
 */
final class ValidateEmailAddress extends AbstractHook
{
    public const HOOK = AbstractHook::HOOK_VALIDATE_REGISTRATION;
    public const PRIORITY = 1000;

    private ContaoFramework $framework;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;
    private EventSubscriber $eventSubscriber;

    // Adapters
    private Adapter $eventMember;

    public function __construct(ContaoFramework $framework, RequestStack $requestStack, TranslatorInterface $translator, EventSubscriber $eventSubscriber)
    {
        $this->framework = $framework;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->eventSubscriber = $eventSubscriber;

        // Adapers
        $this->eventMember = $this->framework->getAdapter(CalendarEventsMemberModel::class);
    }

    /**
     * Important! return false will make the validation fail
     * Validate email address.
     */
    public function __invoke(EventSubscriber $eventSubscriber, EventConfig $eventConfig): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        $request = $this->requestStack->getCurrentRequest();

        $form = $eventSubscriber->getForm();

        // Check if user with submitted email has already booked
        if ($form->hasFormField('email')) {
            $objWidget = $form->getWidget('email');

            if (!empty($objWidget->value)) {
                if (!$eventConfig->get('allowDuplicateEmail')) {
                    $t = $this->eventSubscriber->getTable();

                    $arrOptions = [
                        'column' => [$t.'.email = ?', $t.'.pid = ?'],
                        'value' => [strtolower($objWidget->value), $eventConfig->getModel()->id],
                    ];

                    $objMember = $this->eventMember->findAll($arrOptions);

                    if (null !== $objMember) {
                        $errorMsg = $this->translator->trans('MSC.youHaveAlreadyBooked', [$request->request->get('email')], 'contao_default');
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