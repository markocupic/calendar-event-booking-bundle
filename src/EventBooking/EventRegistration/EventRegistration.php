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

namespace Markocupic\CalendarEventBookingBundle\EventBooking\EventRegistration;

use Codefog\HasteBundle\Form\Form;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Markocupic\CalendarEventBookingBundle\EventBooking\Booking\BookingState;
use Markocupic\CalendarEventBookingBundle\EventBooking\Config\EventConfig;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EventRegistration
{
    public const TABLE = 'tl_calendar_events_member';

    private Form|null $form = null;
    private CalendarEventsMemberModel|null $model = null;
    private array $moduleData = [];

    private Adapter $systemAdapter;
    private Adapter $calendarEventsMemberAdapter;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
    ) {
        $this->systemAdapter = $this->framework->getAdapter(System::class);
        $this->calendarEventsMemberAdapter = $this->framework->getAdapter(CalendarEventsMemberModel::class);
    }

    public function hasModel(): bool
    {
        return null !== $this->model;
    }

    /**
     * @throws \Exception
     */
    public function getModel(): CalendarEventsMemberModel|null
    {
        if (!$this->hasModel()) {
            throw new \Exception('Model not found. Please use the EventRegistration::setModel() method first.');
        }

        return $this->model;
    }

    public function setModel(CalendarEventsMemberModel $model = null): void
    {
        if (null === $model) {
            $model = new CalendarEventsMemberModel();
        }

        $this->model = $model;
    }

    public function getModelFromBookingToken(string $strToken = ''): CalendarEventsMemberModel|null
    {
        return $this->calendarEventsMemberAdapter->findOneByBookingToken($strToken);
    }

    public function validateSubscription(EventConfig $eventConfig): bool
    {
        // Trigger validate event booking request: Check if event is fully booked, if registration deadline has reached, duplicate entries, etc.
        if (isset($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_REGISTRATION]) || \is_array($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_REGISTRATION])) {
            foreach ($GLOBALS['TL_HOOKS'][AbstractHook::HOOK_VALIDATE_REGISTRATION] as $callback) {
                $isValid = $this->systemAdapter->importStatic($callback[0])->{$callback[1]}($this, $eventConfig);

                if (!$isValid) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function unsubscribe(): void
    {
        $eventMember = $this->getModel();
        $eventMember->bookingState = BookingState::STATE_UNSUBSCRIBED;
        $eventMember->unsubscribedOn = time();
        $eventMember->save();
    }

    public function getModuleData(): array
    {
        return $this->moduleData;
    }

    public function setModuleData(array $arrData): void
    {
        $this->moduleData = $arrData;
    }

    public function getTable(): string
    {
        return self::TABLE;
    }
}
