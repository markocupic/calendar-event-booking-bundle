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

namespace Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\PreValidateBookingForm;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\FormFieldModel;
use Contao\FormModel;
use Doctrine\DBAL\Connection;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Listener\ContaoHooks\AbstractHook;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Change submit button label if the registration is put to the waiting list.
 *
 * @Hook(SetFormSubmitLabel::HOOK, priority=SetFormSubmitLabel::PRIORITY)
 */
final class SetFormSubmitLabel extends AbstractHook
{
    public const HOOK = 'calEvtBookingPreValidate';
    public const PRIORITY = 1000;

    private ContaoFramework $framework;
    private Connection $connection;
    private TranslatorInterface $translator;

    // Adapters
    private Adapter $formModel;
    private Adapter $formFieldModel;

    public function __construct(ContaoFramework $framework, Connection $connection, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->translator = $translator;

        // Adapters
        $this->formModel = $framework->getAdapter(FormModel::class);
        $this->formFieldModel = $framework->getAdapter(FormFieldModel::class);
    }

    public function __invoke(CalendarEventBookingEventBookingModuleController $frontendModule): void
    {
        if (!self::isEnabled()) {
            return;
        }

        if (CalendarEventBookingEventBookingModuleController::CASE_WAITING_LIST_POSSIBLE !== $frontendModule->case) {
            return;
        }

        /** @var Form $form */
        if (null === ($form = $frontendModule->objForm)) {
            return;
        }

        $arrFormFields = $form->getFormFields();

        foreach ($arrFormFields as $name => $arrFormField) {
            if (isset($arrFormField['type'], $arrFormField['invisible'])) {
                if ('submit' === $arrFormField['type']) {
                    if (!$arrFormField['invisible']) {
                        $widget = $form->getWidget($name);

                        if ($widget) {
                            $widget->slabel = $this->translator->trans('MSC.addToWaitingList', [], 'contao_default');

                            return;
                        }
                    }
                }
            }
        }
    }
}
