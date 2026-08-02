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

namespace Markocupic\CalendarEventBookingBundle\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\BookingCapacity;
use Markocupic\CalendarEventBookingBundle\Domain\Booking\EventStatusResolver;
use Markocupic\CalendarEventBookingBundle\Event\FrontendModuleGetResponseEvent;
use Markocupic\CalendarEventBookingBundle\Exception\AbstractTranslatableException;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingException;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingRedirectResponseException;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Markocupic\CalendarEventBookingBundle\Request\EventUrlResolver;
use Markocupic\CalendarEventBookingBundle\Template\TemplateDataProvider;
use Markocupic\ContaoFlashMessage\FlashMessage\MessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(EventBookingFormController::TYPE, category: 'events')]
class EventBookingFormController extends AbstractFrontendModuleController
{
    public const string TYPE = 'event_booking_form';

    private const string TRANS_DOMAIN = 'mc_calendar_event_booking';

    private const string REQUEST_ATTR_MODULE = '_event_booking_form_module';

    private const string REQUEST_KEY_FORM_SUBMIT = 'FORM_SUBMIT';

    private const string REQUEST_KEY_TICKET_AMOUNT = 'ticketAmount';

    private const string FORM_FIELD_WAITING_LIST = 'waitingList';

    private const string TEMPLATE_KEY_FORM_MARKUP = 'form_markup';

    public bool $waitingListOpen = false;

    private CalendarModel|null $calendar = null;

    private CalendarEventsModel|null $calEvent = null;

    private FormModel|null $form = null;

    private string|null $eventStatus = null;

    public function __construct(
        private readonly TemplateDataProvider $templateDataProvider,
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly BookingCapacity $bookingCapacity,
        private readonly EventStatusResolver $eventStatusResolver,
        private readonly EventUrlResolver $eventUrlResolver,
        private readonly LockFactory $lockFactory,
        #[Autowire(service: 'markocupic_calendar_event_booking.flash_message.form')]
        private readonly MessageInterface $message,
        private readonly RateLimiterFactory $rateLimiterFactory,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
        private readonly bool $rateLimitBookingFormEnable,
        private readonly LoggerInterface|null $contaoErrorLogger,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->calEvent = $this->eventUrlResolver->resolve();

            if (null === $this->calEvent || !$this->calEvent->published || !$this->calEvent->enableBookingForm || null === ($this->calendar = $this->calEvent->getRelated('pid'))) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

        // Call the parent method
        return parent::__invoke($request, $model, $section, $classes);
    }

    public function getCalendar(): CalendarModel|null
    {
        return $this->calendar;
    }

    public function getEvent(): CalendarEventsModel|null
    {
        return $this->calEvent;
    }

    public function getForm(): FormModel|null
    {
        return $this->form;
    }

    public function getEventStatus(): string|null
    {
        return $this->eventStatus;
    }

    /**
     * @throws Exception
     * @throws \Throwable
     */
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        // Normally, if no event is set, the __invoke() method will return an empty response.
        if (null === $this->calEvent && $this->scopeMatcher->isFrontendRequest($request)) {
            throw new \LogicException('Event booking form module is not available due to missing event object.');
        }

        // Attach the module instance to the request so that Contao hooks and event
        // listeners can access it later.
        $request->attributes->set(self::REQUEST_ATTR_MODULE, $this);

        // Allow other modules to modify the form ID, the template variables or the response.
        $event = $this->dispatchGetResponseEvent($template, $model, $request);

        if ($event->hasResponse()) {
            return $event->getResponse();
        }

        $this->getContaoAdapter(System::class)->loadLanguageFile(CalendarEventsMemberModel::getTable());
        $this->eventStatus = $this->eventStatusResolver->resolveEventStatus($this->calEvent, $request);

        $formId = $event->getOptions()['formId'] ?? -1;
        $this->form = $this->getContaoAdapter(FormModel::class)->findById($formId);

        if (!$this->eventStatusResolver->canRegister($this->calEvent, $request)) {
            $this->assertValidBookingForm($formId);

            $this->applyTemplateData($template, $request);

            return $template->getResponse();
        }

        $this->openWaitingListIfAvailable($formId);

        $this->processBooking($template, $request, $formId);

        $this->applyTemplateData($template, $request);

        return $template->getResponse();
    }

    private function dispatchGetResponseEvent(FragmentTemplate $template, ModuleModel $model, Request $request): FrontendModuleGetResponseEvent
    {
        $event = new FrontendModuleGetResponseEvent($template, $model, $request, $this, ['formId' => $model->form]);
        $this->eventDispatcher->dispatch($event);

        return $event;
    }

    private function openWaitingListIfAvailable(int $formId): void
    {
        if (!$this->isWaitingListAvailable()) {
            return;
        }

        $this->waitingListOpen = true;

        // Show the waiting list checkbox now that the waiting list is available.
        $this->setFormFieldVisibility($formId, self::FORM_FIELD_WAITING_LIST, true);
    }

    private function isWaitingListAvailable(): bool
    {
        return $this->bookingCapacity->isFullyBooked($this->calEvent)
            && !$this->bookingCapacity->isWaitingListFull($this->calEvent);
    }

    /**
     * Renders the booking form inside an event-specific lock and a database
     * transaction so that concurrent bookings are serialized per event.
     *
     * @throws Exception
     * @throws \Throwable
     */
    private function processBooking(FragmentTemplate $template, Request $request, int $formId): void
    {
        $lock = $this->lockFactory->createLock(self::class.'_'.($this->calEvent?->id ?? 0));
        $lock->acquire(true);

        $this->connection->beginTransaction();

        try {
            if ($request->request->get(self::REQUEST_KEY_FORM_SUBMIT) === $this->getFormId($formId)) {
                $this->handleBookingSubmission($request);
            }

            // Use Contao core hooks to customize the form processing. Throw an
            // EventBookingException to stop the form processing. Throw an
            // EventBookingRedirectResponseException to roll back the transaction and
            // redirect to a new URL...
            $template->set(self::TEMPLATE_KEY_FORM_MARKUP, $this->getContaoAdapter(Controller::class)->getForm($formId));

            $this->connection->commit();
        } catch (RedirectResponseException $e) {
            // !important: Otherwise new inserts to the booking table won't be persisted on
            // page redirects after a successful booking (tl_form.jumpTo).
            $this->connection->commit();

            throw $e;
        } catch (EventBookingRedirectResponseException $e) {
            $this->connection->rollBack();

            throw $e;
        } catch (AbstractTranslatableException $e) {
            $this->connection->rollBack();
            $this->message->add($this->translator->trans($e->getTranslatableText(), $e->getMessageData(), $e->getMessageDomain()), $e->getSeverityLevel());
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            $this->message->addError($this->translator->trans('mod_form.error.unexpected_error', [], self::TRANS_DOMAIN));
            $this->contaoErrorLogger?->error($e->getMessage());
        } finally {
            $lock->release();
        }
    }

    private function handleBookingSubmission(Request $request): void
    {
        // Protect the form against too many requests.
        $this->checkRateLimit($request);

        $requestedTicketAmount = (int) $request->request->get(self::REQUEST_KEY_TICKET_AMOUNT, 1);

        if ($this->shouldFallBackToWaitingList($requestedTicketAmount)) {
            $this->waitingListOpen = true;
        }
    }

    private function shouldFallBackToWaitingList(int $requestedTicketAmount): bool
    {
        return !$this->bookingCapacity->canFulfillBookingRequest($this->calEvent, $requestedTicketAmount)
            && $this->bookingCapacity->canFulfillBookingRequestWaitingList($this->calEvent, $requestedTicketAmount);
    }

    private function setFormFieldVisibility(int $formId, string $name, bool $blnShow = true): void
    {
        $formField = $this->getContaoAdapter(FormFieldModel::class)->findOneBy(['name = ?', 'pid = ?'], [$name, $formId]);

        if (null === $formField) {
            throw new \Exception(\sprintf('Form field "%s" not found.', $name));
        }

        $formField->invisible = !$blnShow;

        $formField->save();
    }

    /**
     * Ensures a valid booking form is assigned to the module and returns it.
     */
    private function assertValidBookingForm(int $formId): FormModel
    {
        $form = $this->getContaoAdapter(FormModel::class)->findById($formId);

        if (null === $form) {
            throw new \Exception('No booking form assigned to the booking module.');
        }

        if (!$form->isCalendarEventBookingForm) {
            throw new \Exception('Invalid booking form ID '.$form->id.' attached to the event booking form module. Please enable the "isCalendarEventBookingForm" flag in the form settings in the Contao backend.');
        }

        return $form;
    }

    /**
     * We need the form id to target the submitted form (FORM_SUBMIT).
     */
    private function getFormId(int $formId): string
    {
        $form = $this->assertValidBookingForm($formId);

        return $form->formID ? 'auto_'.$form->formID : 'auto_form_'.$form->id;
    }

    private function applyTemplateData(FragmentTemplate $template, Request $request): void
    {
        $template->set('eventStatus', $this->eventStatus);
        $template->set('eventStatusText', $this->getEventStatusText());
        $template->set('waitingListOpen', $this->waitingListOpen);
        $template->set('messagesUnwrapped', $this->message->renderUnwrapped(peek: true));
        $template->set('messages', $this->message->hasMessages() ? $this->message->getAll() : null);

        $this->templateDataProvider->addData($template, $this->calEvent, $request);
    }

    private function getEventStatusText(): string
    {
        return match ($this->eventStatus) {
            EventStatusResolver::NOT_YET_BOOKABLE => $this->translator->trans(
                'MSC.'.$this->eventStatus,
                [$this->getContaoAdapter(Date::class)->parse($this->getContaoAdapter(Config::class)->get('datimFormat'), $this->calEvent->bookingStartDate)],
                'contao_default',
            ),
            default => $this->translator->trans('MSC.'.$this->eventStatus, [], 'contao_default'),
        };
    }

    private function checkRateLimit(Request $request): void
    {
        if ($this->rateLimitBookingFormEnable && !empty($request->getClientIp())) {
            $limiter = $this->rateLimiterFactory->create($request->getClientIp());

            if (!$limiter->consume()->isAccepted()) {
                throw new EventBookingException('Too many requests!', SeverityLevel::ERROR, 'mod_form.error.too_many_requests', [], self::TRANS_DOMAIN);
            }
        }
    }
}
