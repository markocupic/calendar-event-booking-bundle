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
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook(ValidateEmailAddress::HOOK, priority=ValidateEmailAddress::PRIORITY)
 */
final class ValidateEmailAddress extends AbstractHook
{
    public const HOOK = 'calEvtBookingValidateRegistration';
    public const PRIORITY = 1000;

    private ContaoFramework $framework;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;

    // Adapters
    private Adapter $eventMember;

    public function __construct(ContaoFramework $framework, RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->requestStack = $requestStack;
        $this->translator = $translator;

        // Adapers
        $this->eventMember = $this->framework->getAdapter(CalendarEventsMemberModel::class);
    }

    /**
     * Important! return false will make the validation fail
     * Validate email address.
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        $request = $this->requestStack->getCurrentRequest();

        /** @var Form $objForm */
        $objForm = $moduleInstance->getProperty('objForm');

        /** @var EventConfig $eventConfig */
        $eventConfig = $moduleInstance->getProperty('eventConfig');

        // Check if user with submitted email has already booked
        if ($objForm->hasFormField('email')) {
            $objWidget = $objForm->getWidget('email');

            if (!empty($objWidget->value)) {
                if (!$eventConfig->get('allowDuplicateEmail')) {
                    $t = CalendarEventBookingEventBookingModuleController::EVENT_SUBSCRIPTION_TABLE;
                    $arrOptions = [
                        'column' => [$t.'.email = ?', $t.'.pid = ?'],
                        'value' => [strtolower($objWidget->value), $eventConfig->getEvent()->id],
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
