<?php

declare(strict_types=1);

/*
 * This file is part of Calendar Event Booking Bundle.
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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingException;
use Markocupic\CalendarEventBookingBundle\Exception\EventBookingRedirectResponseException;
use Markocupic\CalendarEventBookingBundle\Exception\SeverityLevel;
use Markocupic\CalendarEventBookingBundle\Helper\AddTemplateData;
use Markocupic\CalendarEventBookingBundle\Helper\EventBooking;
use Markocupic\CalendarEventBookingBundle\Helper\EventStatus;
use Markocupic\CalendarEventBookingBundle\Model\CalendarEventsMemberModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(EventBookingFormController::TYPE, category: 'events', template: 'mod_event_booking_form')]
class EventBookingFormController extends AbstractFrontendModuleController
{
    public const TYPE = 'event_booking_form';

    private const TRANS_DOMAIN = 'mc_calendar_event_booking';

    public bool $waitingListOpen = false;

    private CalendarModel|null $calendar = null;

    private CalendarEventsModel|null $event = null;

    private string|null $eventStatus = null;

    public function __construct(
        private readonly AddTemplateData $addTemplateData,
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly EventBooking $eventBooking,
        private readonly LockFactory $lockFactory,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
        private readonly RateLimiterFactoryInterface $rateLimiterFactory,
        private readonly LoggerInterface|null $contaoErrorLogger,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        if ($page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->event = $this->eventBooking->getEventFromCurrentUrl();

            if (null === $this->event || !$this->event->published || !$this->event->enableBookingForm || null === ($this->calendar = $this->event->getRelated('pid'))) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

        // Call the parent method
        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * @throws \Exception
     */
    public function getCalendar(): CalendarModel|null
    {
        return $this->calendar;
    }

    /**
     * @throws \Exception
     */
    public function getEvent(): CalendarEventsModel|null
    {
        return $this->event;
    }

    public function getEventStatus(): string|null
    {
        return $this->eventStatus;
    }

    /**
     * @throws Exception
     * @throws \Throwable
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // Attach the event booking form module instance to the request so that we can
        // access it later in the Contao Hooks or event listeners
        $request->attributes->set('_event_booking_form_module', $this);

        $this->framework->getAdapter(System::class)->loadLanguageFile(CalendarEventsMemberModel::getTable());

        $this->eventStatus = $this->eventBooking->resolveEventStatus($this->event, $request);

        if (!$this->eventBooking->canRegister($this->event, $request) && $this->getFormId($model->form)) {
            $this->addTemplateData($template, $request);

            return $template->getResponse();
        }

        if ($this->eventBooking->isFullyBooked($this->event) && !$this->eventBooking->isWaitingListFull($this->event)) {
            $this->waitingListOpen = true;
            // Show the waitingList checkbox
            $this->setFormFieldVisibility($model->form, 'waitingList', true);
        }

        $lock = $this->lockFactory->createLock(self::class);
        $lock->acquire(true);

        $this->connection->beginTransaction();

        try {
            if ($request->request->get('FORM_SUBMIT') === $this->getFormId($model->form)) {
                $limiter = $this->rateLimiterFactory->create($request->getClientIp());

                if (!$limiter->consume()->isAccepted()) {
                    throw new EventBookingException('too many requests', $this->translator->trans('mod_form.error.too_many_requests', [], self::TRANS_DOMAIN), SeverityLevel::ERROR);
                }

                // Get the ticket amount from POST (default: 1)
                $requestedTicketAmount = (int) $request->request->get('ticketAmount', 1);

                if (!$this->eventBooking->canFulfillBookingRequest($this->event, $requestedTicketAmount)) {
                    if ($this->eventBooking->canFulfillBookingRequestWaitingList($this->event, $requestedTicketAmount)) {
                        $this->waitingListOpen = true;
                    }
                }
            }

            $template->form_markup = $this->framework->getAdapter(Controller::class)->getForm($model->form);

            // Use Contao core hooks to customize the form processing.
            // Throw an EventBookingException exception to stop the form processing.
            // Throw an EventBookingRedirectResponseException to roll back the transaction and redirect to a new URL...

            $this->connection->commit();
        } catch (RedirectResponseException $e) {
            // !important: Otherwise new inserts to the booking table won't be persisted on
            // page redirects after a successful booking (tl_form.jumpTo).
            $this->connection->commit();

            throw $e;
        }catch (EventBookingRedirectResponseException $e) {
            $this->connection->rollBack();
            throw $e;
        } catch (EventBookingException $e) {
            $this->connection->rollBack();
            $this->framework->getAdapter(Message::class)->add($e->getTranslatableText(), $e->getSeverityLevel());
        }  catch (\throwable $e) {
            $this->connection->rollBack();
            $this->framework->getAdapter(Message::class)->addError($this->translator->trans('mod_form.error.unexpected_error', [], self::TRANS_DOMAIN));

            if (method_exists($e, 'getMessage')) {
                $this->contaoErrorLogger?->error($e->getMessage());
            }

            throw $e;
        } finally {
            $lock->release();
        }

        $this->addTemplateData($template, $request);

        return $template->getResponse();
    }

    private function setFormFieldVisibility(int $formId, string $name, bool $blnShow = true): void
    {
        $formField = FormFieldModel::findOneBy(['name = ?', 'pid = ?'], [$name, $formId]);

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
        $form = $this->framework->getAdapter(FormModel::class)->findById($formId);

        if (null === $form) {
            throw new \Exception('No booking form assigned to the booking module.');
        }

        if (!$form->isCalendarEventBookingForm) {
            throw new \Exception('Invalid booking form ID '.$form->id.' attached to the event booking form module. Please enable the "isCalendarEventBookingForm" flag in the form settings in the Contao backend.');
        }

        return $form->formID ? 'auto_'.$form->formID : 'auto_form_'.$form->id;
    }

    private function addTemplateData(Template $template, Request $request): void
    {
        $template->eventStatus = $this->eventStatus;

        $template->eventStatusText = match ($this->eventStatus) {
            EventStatus::NOT_YET_BOOKABLE => function () {
                $dateFormat = $this->framework->getAdapter(Config::class)->get('dateFormat');

                return $this->translator->trans('MSC.'.$this->eventStatus, [$dateFormat, $this->event->bookingStartDate], 'contao_default');
            },
            default => $this->translator->trans('MSC.'.$this->eventStatus, [], 'contao_default'),
        };

        $template->waitingListOpen = $this->waitingListOpen;
        $template->messages = $this->framework->getAdapter(Message::class)->hasMessages() ? $this->framework->getAdapter(Message::class)->generate('FE') : null;
        $this->addTemplateData->addTemplateData($template, $this->event, $request);
    }
}
