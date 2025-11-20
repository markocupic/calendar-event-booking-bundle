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

namespace Markocupic\CalendarEventBookingBundle\Event;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class FrontendModuleGetResponseEvent extends Event
{
    private Response|null $response = null;

    public function __construct(
        private readonly FragmentTemplate $template,
        private readonly ModuleModel $model,
        private readonly Request $request,
        private readonly AbstractFrontendModuleController $controller,
        private array $options = [],
    ) {
    }

    public function getTemplate(): FragmentTemplate
    {
        return $this->template;
    }

    public function getModel(): ModuleModel
    {
        return $this->model;
    }

    public function getRequest(): Request|null
    {
        return $this->request;
    }

    public function getController(): AbstractFrontendModuleController
    {
        return $this->controller;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function hasResponse(): bool
    {
        return null !== $this->response;
    }

    public function getResponse(): Response|null
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
