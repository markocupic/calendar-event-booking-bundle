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

namespace Markocupic\CalendarEventBookingBundle\Listener\Validator;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Input;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validate email input.
 *
 * @Hook(EmailValidator::HOOK)
 */
class EmailValidator
{
    public const HOOK = 'calEvtBookingValidateSubscriptionRequest';

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->translator = $translator;
    }

    public function __invoke(Form $objForm, CalendarEventsModel $objEvent): bool
    {
        /** @var CalendarEventsMemberModel $calendarEventsMemberModelAdapter */
        $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);

        /** @var Input $inputAdapter */
        $inputAdapter = $this->framework->getAdapter(Input::class);

        // Check if user with submitted email has already booked
        if ($objForm->hasFormField('email')) {
            $objWidget = $objForm->getWidget('email');

            if (!empty($objWidget->value)) {
                if (!$objEvent->enableMultiBookingWithSameAddress) {
                    $t = CalendarEventBookingEventBookingModuleController::EVENT_SUBSCRIPTION_TABLE;
                    $arrOptions = [
                        'column' => [$t.'.email=?', $t.'.pid=?'],
                        'value' => [strtolower($objWidget->value), $objEvent->id],
                    ];

                    $objMember = $calendarEventsMemberModelAdapter->findAll($arrOptions);

                    if (null !== $objMember) {
                        $errorMsg = $this->translator->trans('MSC.youHaveAlreadyBooked', [$inputAdapter->post('email')], 'contao_default');
                        $objWidget->addError($errorMsg);

                        return false;
                    }
                }
            }
        }

        return true;
    }
}
