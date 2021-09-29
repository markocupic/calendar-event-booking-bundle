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

namespace Markocupic\CalendarEventBookingBundle\Listener\AddField;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Haste\Form\Form;
use Markocupic\CalendarEventBookingBundle\Controller\FrontendModule\CalendarEventBookingEventBookingModuleController;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Add field "escort".
 *
 * @Hook(Escort::HOOK)
 */
class Escort
{
    public const HOOK = 'calEvtBookingAddField';

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

    public function __invoke(Form $objForm, string $strField, array $arrDca, CalendarEventsModel $objEvent, CalendarEventBookingEventBookingModuleController $instance): bool
    {
        // Skip input field "escorts" if escorts are not allowed
        if ('escorts' === $strField) {
            if ($objEvent->maxEscortsPerMember < 1) {
                return false;
            }
        }

        return true;
    }
}
