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

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Markocupic\CalendarEventBookingBundle\Event\PostBookingEvent;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ValidateEmailAddressSubscriber implements EventSubscriberInterface
{
    public const PRIORITY = 1000;

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

    public static function getSubscribedEvents(): array
    {
        return [
            PostBookingEvent::NAME => ['validateEmailAddress', self::PRIORITY],
        ];
    }

    /**
     * Important! Only stopping the event propagation will make the validation fail
     * Validate email address.
     */
    public function validateEmailAddress(PostBookingEvent $event): void
    {
        if ($event->isDisabled(self::class)) {
            return;
        }

        $calendarEventsMemberModelAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);
        $inputAdapter = $this->framework->getAdapter(Input::class);

        /** @var CalendarEventBookingEventBookingModuleController $moduleInstance */
        $moduleInstance = $event->getBookingModuleInstance();

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
                        'column' => [$t.'.email=?', $t.'.pid=?'],
                        'value' => [strtolower($objWidget->value), $objEvent->id],
                    ];

                    $objMember = $calendarEventsMemberModelAdapter->findAll($arrOptions);

                    if (null !== $objMember) {
                        $errorMsg = $this->translator->trans('MSC.youHaveAlreadyBooked', [$inputAdapter->post('email')], 'contao_default');
                        $objWidget->addError($errorMsg);

                        // Stopping the event propagation will make the validation fail
                        $event->stopPropagation();
                    }
                }
            }
        }
    }
}