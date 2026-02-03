<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * Contributions by Kirsten Roschanski <support@inszenium.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\LoadFormField;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;
use Markocupic\CalendarEventBookingBundle\EventListener\ContaoHooks\AbstractHook;
use Markocupic\CalendarEventBookingBundle\Util\CheckoutUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Redirect handler for Express Checkout.
 *
 * Modifies the booking form's jumpTo page to redirect to the confirmation page
 * (cebb_jumpToOnCheckoutCompletion) instead of the default form jumpTo page.
 *
 * This hook runs when the form is first loaded, ensuring the redirect is set
 * before any form processing occurs.
 *
 * @author Kirsten Roschanski <support@inszenium.de>
 */
#[AsHook('loadFormField', priority: 1000)]
final class ExpressCheckoutFormRedirect extends AbstractHook
{
    public const HOOK = 'loadFormField';

    public function __construct(
        private readonly CheckoutUtil $checkoutUtil,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Modify form jumpTo for express checkout mode.
     *
     * @param Widget $objWidget The form field widget
     * @param string $formId    The form ID
     * @param array  $arrForm   The form configuration
     * @param Form   $objForm   The form object
     *
     * @return Widget The widget
     */
    public function __invoke(Widget $objWidget, string $formId, array $arrForm, Form $objForm): Widget
    {
        if (!$this->isEnabled()) {
            return $objWidget;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return $objWidget;
        }

        try {
            $moduleModel = $this->checkoutUtil->getModuleModel($request);
            
            // Check if this is the express checkout
            $checkoutType = $moduleModel->cebb_checkoutType ?? 'default';
            
            if ('express' === $checkoutType && !empty($moduleModel->cebb_jumpToOnCheckoutCompletion)) {
                $objForm->jumpTo = (int)$moduleModel->cebb_jumpToOnCheckoutCompletion;
            }
        } catch (\Exception $e) {
            $this->logger->error('ExpressCheckoutFormRedirect - Error: ' . $e->getMessage());
        }

        return $objWidget;
    }
}
