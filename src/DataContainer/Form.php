<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\FormModel;

class Form
{
    /**
     * Manipulate fields.
     */
    #[AsCallback(table: 'tl_form', target: 'config.onload')]
    public function manipulateFieldsDca(DataContainer $dc): void
    {
        if (null === ($form = FormModel::findById($dc->id))) {
            return;
        }

        if (!$form->isCalendarEventBookingForm) {
            return;
        }

        if (
            !isset($GLOBALS['TL_DCA']['tl_form']['fields']['novalidate']['eval']['tl_class'])
            || !\is_string($GLOBALS['TL_DCA']['tl_form']['fields']['novalidate']['eval']['tl_class'])
        ) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_form']['fields']['novalidate']['eval']['tl_class'] = 'clr w100';
    }
}
