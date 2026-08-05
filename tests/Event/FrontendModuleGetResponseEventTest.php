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

namespace Markocupic\CalendarEventBookingBundle\Tests\Event;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CalendarEventBookingBundle\Event\FrontendModuleGetResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendModuleGetResponseEventTest extends ContaoTestCase
{
    public function testExposesConstructorArguments(): void
    {
        $template = $this->createTemplate();
        $model = $this->createClassWithPropertiesMock(ModuleModel::class);
        $request = new Request();
        $controller = $this->createMock(AbstractFrontendModuleController::class);

        $event = new FrontendModuleGetResponseEvent($template, $model, $request, $controller, ['formId' => 5]);

        $this->assertSame($template, $event->getTemplate());
        $this->assertSame($model, $event->getModel());
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($controller, $event->getController());
        $this->assertSame(['formId' => 5], $event->getOptions());
    }

    public function testOptionsCanBeReplaced(): void
    {
        $event = $this->createEvent();

        $event->setOptions(['formId' => 9]);

        $this->assertSame(['formId' => 9], $event->getOptions());
    }

    public function testResponseHandling(): void
    {
        $event = $this->createEvent();

        $this->assertFalse($event->hasResponse());
        $this->assertNull($event->getResponse());

        $response = new Response();
        $event->setResponse($response);

        $this->assertTrue($event->hasResponse());
        $this->assertSame($response, $event->getResponse());
    }

    private function createEvent(): FrontendModuleGetResponseEvent
    {
        return new FrontendModuleGetResponseEvent(
            $this->createTemplate(),
            $this->createClassWithPropertiesMock(ModuleModel::class),
            new Request(),
            $this->createMock(AbstractFrontendModuleController::class),
        );
    }

    private function createTemplate(): FragmentTemplate
    {
        return new FragmentTemplate(
            'form',
            static fn (FragmentTemplate $t, Response|null $response = null): Response => $response ?? new Response(),
        );
    }
}
