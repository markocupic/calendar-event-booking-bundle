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
use Contao\Input;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook(ValidateEmailAddress::HOOK, priority: 1000)]
final class ValidateEmailAddress
{
    public const HOOK = 'calEvtBookingValidateBookingRequest';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Important! return false will make the validation fail
     * Validate email address.
     */
    public function __invoke(CalendarEventBookingEventBookingModuleController $moduleInstance, array $arrDisabledHooks = []): bool
    {
        if (\in_array(self::class, $arrDisabledHooks, true)) {
            return true;
        }

        $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $inputAdapter = $this->framework->getAdapter(Input::class);

        /** @var Form $objForm */
        $objForm = $moduleInstance->getProperty('objForm');

        /** @var CalendarEventsModel $objEvent */
        $objEvent = $moduleInstance->getProperty('objEvent');

        // Check if user with submitted email has already booked
        if ($objForm->hasFormField('email')) {
            $objWidget = $objForm->getWidget('email');

            if (!empty($objWidget->value)) {
                if (!$objEvent->enableMultiBookingWithSameAddress) {
                    $t = CalendarEventBookingEventBookingModuleController::EVENT_SUBSCRIPTION_TABLE;
                    $arrOptions = [
                        'column' => [$t.'.email = ?', $t.'.pid = ?'],
                        'value' => [strtolower($objWidget->value), $objEvent->id],
                    ];

                    $objMember = $calendarEventsMemberModelAdapter->findAll($arrOptions);

                    if (null !== $objMember) {
                        $errorMsg = $this->translator->trans('MSC.youHaveAlreadyBooked', [$inputAdapter->post('email')], 'contao_default');
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
