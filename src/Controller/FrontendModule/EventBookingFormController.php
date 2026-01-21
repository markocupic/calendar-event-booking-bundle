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
use Markocupic\CalendarEventBookingBundle\Event\FrontendModuleGetResponseEvent;
use Markocupic\CalendarEventBookingBundle\Exception\AbstractTranslatableException;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingException;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingRedirectResponseException;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Helper\AddTemplateData;
use Markocupic\CalendarEventBookingBundle\Helper\EventStatus;
use Markocupic\CalendarEventBookingBundle\Helper\EventUrlResolver;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
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
    public const TYPE = 'event_booking_form';

    private const TRANS_DOMAIN = 'mc_calendar_event_booking';

    public bool $waitingListOpen = false;

    private CalendarModel|null $calendar = null;

    private CalendarEventsModel|null $event = null;

    private FormModel|null $form = null;

    private string|null $eventStatus = null;

    public function __construct(
        private readonly AddTemplateData $addTemplateData,
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EventStatus $eventStatusHelper,
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
            $this->event = $this->eventUrlResolver->resolve();

            if (null === $this->event || !$this->event->published || !$this->event->enableBookingForm || null === ($this->calendar = $this->event->getRelated('pid'))) {
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
        return $this->event;
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
        // Attach the event booking form module instance to the request so that we can
        // access it later in the Contao Hooks or event listeners.
        $request->attributes->set('_event_booking_form_module', $this);

        // Allow other modules to modify the form ID, the template variables or the response.
        $event = new FrontendModuleGetResponseEvent($template, $model, $request, $this, ['formId' => $model->form]);
        $this->eventDispatcher->dispatch($event);

        if ($event->hasResponse()) {
            return $event->getResponse();
        }

        $this->getContaoAdapter(System::class)->loadLanguageFile(CalendarEventsMemberModel::getTable());

        $this->eventStatus = $this->eventStatusHelper->resolveEventStatus($this->event, $request);

        $formId = $event->getOptions()['formId'] ?? -1;

        $this->form = $this->getContaoAdapter(FormModel::class)->findById($formId);

        if (!$this->eventStatusHelper->canRegister($this->event, $request) && $this->getFormId($formId)) {
            $this->addTemplateData($template, $request);

            return $template->getResponse();
        }

        if ($this->eventStatusHelper->isFullyBooked($this->event, $this->connection) && !$this->eventStatusHelper->isWaitingListFull($this->event, $this->connection)) {
            $this->waitingListOpen = true;
            // Show the waitingList checkbox if the waiting list is available
            $this->setFormFieldVisibility($formId, 'waitingList', true);
        }

        $lock = $this->lockFactory->createLock(self::class);
        $lock->acquire(true);

        $this->connection->beginTransaction();

        try {
            if ($request->request->get('FORM_SUBMIT') === $this->getFormId($formId)) {
                // Protect form against too many requests
                $this->checkRateLimit($request);

                // Get the ticket amount from POST (default: 1)
                $requestedTicketAmount = (int) $request->request->get('ticketAmount', 1);

                if (!$this->eventStatusHelper->canFulfillBookingRequest($this->event, $this->connection, $requestedTicketAmount)) {
                    if ($this->eventStatusHelper->canFulfillBookingRequestWaitingList($this->event, $this->connection, $requestedTicketAmount)) {
                        $this->waitingListOpen = true;
                    }
                }
            }

            // Use Contao core hooks to customize the form processing. Throw an
            // EventBookingException exception to stop the form processing. Throw an
            // EventBookingRedirectResponseException to roll back the transaction and
            // redirect to a new URL...
            $template->set('form_markup', $this->getContaoAdapter(Controller::class)->getForm($formId));

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
        } catch (\throwable $e) {
            $this->connection->rollBack();
            $this->message->addError($this->translator->trans('mod_form.error.unexpected_error', [], self::TRANS_DOMAIN));
            $this->contaoErrorLogger?->error($e->getMessage());

            throw $e;
        } finally {
            $lock->release();
        }

        $this->addTemplateData($template, $request);

        return $template->getResponse();
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
     * We need the form id to target the form submitted (FORM_SUBMIT).
     */
    private function getFormId(int $formId): string
    {
        $form = $this->getContaoAdapter(FormModel::class)->findById($formId);

        if (null === $form) {
            throw new \Exception('No booking form assigned to the booking module.');
        }

        if (!$form->isCalendarEventBookingForm) {
            throw new \Exception('Invalid booking form ID '.$form->id.' attached to the event booking form module. Please enable the "isCalendarEventBookingForm" flag in the form settings in the Contao backend.');
        }

        return $form->formID ? 'auto_'.$form->formID : 'auto_form_'.$form->id;
    }

    private function addTemplateData(FragmentTemplate $template, Request $request): void
    {
        $template->set('eventStatus', $this->eventStatus);

        $template->set('eventStatusText', match ($this->eventStatus) {
            EventStatus::NOT_YET_BOOKABLE => $this->translator->trans('MSC.'.$this->eventStatus, [$this->getContaoAdapter(Date::class)->parse($this->getContaoAdapter(Config::class)->get('datimFormat'), $this->event->bookingStartDate)], 'contao_default'),
            default => $this->translator->trans('MSC.'.$this->eventStatus, [], 'contao_default'),
        });

        $template->set('waitingListOpen', $this->waitingListOpen);
        $template->set('messages', $this->message->hasMessages() ? $this->message->getAll() : null);
        $this->addTemplateData->addTemplateData($template, $this->event, $request);
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
